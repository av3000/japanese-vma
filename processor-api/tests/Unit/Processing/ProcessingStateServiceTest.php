<?php

declare(strict_types=1);

namespace Tests\Unit\Processing;

use App\Application\LastOperations\Events\AsyncLastOperationStatusUpdated;
use App\Application\Processing\Services\ProcessingStateService;
use App\Application\Processing\Services\ProcessingStateServiceInterface;
use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\Enums\ProcessingTaskType;
use App\Domain\Shared\Enums\LastOperationStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/**
 * Every write through the service reaches clients exactly once as a snapshot of what was
 * just written (audit F-08 kept intact across the ADR 0001 table change).
 */
class ProcessingStateServiceTest extends TestCase
{
    use RefreshDatabase;

    private const TASK = ProcessingTaskType::ArticleContentProcessing;

    private ProcessingStateServiceInterface $service;

    private EntityId $id;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ProcessingStateServiceInterface::class);
        $this->id = EntityId::from((string) Str::uuid());
        Event::fake([AsyncLastOperationStatusUpdated::class]);
    }

    public function test_is_bound_to_the_concrete_service(): void
    {
        $this->assertInstanceOf(ProcessingStateService::class, $this->service);
    }

    public function test_each_transition_broadcasts_once_with_the_status_just_written(): void
    {
        $this->service->startOrReset(ProcessingEntityType::Article, $this->id, self::TASK, 1);
        $this->service->markProcessing(ProcessingEntityType::Article, $this->id, self::TASK, 1);
        $this->service->markCompleted(ProcessingEntityType::Article, $this->id, self::TASK, ['kanji_count' => 1, 'word_count' => 2]);

        Event::assertDispatchedTimes(AsyncLastOperationStatusUpdated::class, 3);

        $statuses = [];
        Event::assertDispatched(AsyncLastOperationStatusUpdated::class, function (AsyncLastOperationStatusUpdated $event) use (&$statuses): bool {
            $statuses[] = $event->status();

            return $event->entityUuid === $this->id->value()
                && $event->snapshot['type'] === self::TASK->value;
        });
        $this->assertSame(
            [LastOperationStatus::PENDING, LastOperationStatus::PROCESSING, LastOperationStatus::COMPLETED],
            $statuses,
        );
    }

    public function test_mark_failed_sanitises_the_exception_and_records_its_class(): void
    {
        $this->service->startOrReset(ProcessingEntityType::Article, $this->id, self::TASK, 1);

        $state = $this->service->markFailed(
            ProcessingEntityType::Article,
            $this->id,
            self::TASK,
            'kanji',
            new RuntimeException("Job exceeded\n   the 120 second timeout"),
            ['stage' => 'kanji'],
        );

        $this->assertSame(LastOperationStatus::FAILED, $state?->status);
        $this->assertSame('kanji', $state?->errorCode);
        $this->assertSame('Job exceeded the 120 second timeout', $state?->errorMessage);
        $this->assertSame(['stage' => 'kanji', 'exception' => RuntimeException::class], $state?->metadata);
    }

    public function test_transitions_without_a_row_broadcast_nothing(): void
    {
        $this->assertNull($this->service->markProcessing(ProcessingEntityType::Article, $this->id, self::TASK, 1));
        $this->assertNull($this->service->markSuperseded(ProcessingEntityType::Article, $this->id, self::TASK, 1));

        Event::assertNotDispatched(AsyncLastOperationStatusUpdated::class);
    }

    public function test_record_failure_fails_only_a_non_terminal_row(): void
    {
        $this->service->recordFailure(ProcessingEntityType::Article, $this->id, self::TASK, new RuntimeException('no row'), 1);
        Event::assertNotDispatched(AsyncLastOperationStatusUpdated::class);

        $this->service->startOrReset(ProcessingEntityType::Article, $this->id, self::TASK, 1);
        $this->service->recordFailure(ProcessingEntityType::Article, $this->id, self::TASK, new RuntimeException('killed'), 3);
        Event::assertDispatched(
            AsyncLastOperationStatusUpdated::class,
            fn (AsyncLastOperationStatusUpdated $event): bool => $event->status() === LastOperationStatus::FAILED,
        );

        $this->service->startOrReset(ProcessingEntityType::Article, $this->id, self::TASK, 2);
        $this->service->markCompleted(ProcessingEntityType::Article, $this->id, self::TASK, []);
        Event::assertDispatchedTimes(AsyncLastOperationStatusUpdated::class, 4);

        $this->service->recordFailure(ProcessingEntityType::Article, $this->id, self::TASK, new RuntimeException('late'), 3);
        Event::assertDispatchedTimes(AsyncLastOperationStatusUpdated::class, 4, 'A completed row is left alone.');
    }

    public function test_sweep_stale_fails_old_non_terminal_rows_with_the_stale_code(): void
    {
        $this->service->startOrReset(ProcessingEntityType::Article, $this->id, self::TASK, 1);
        \Illuminate\Support\Facades\DB::table('processing_states')->update(['updated_at' => now()->subMinutes(10)]);

        $this->assertSame(1, $this->service->sweepStale(300));

        Event::assertDispatched(
            AsyncLastOperationStatusUpdated::class,
            fn (AsyncLastOperationStatusUpdated $event): bool => $event->status() === LastOperationStatus::FAILED
                && $event->snapshot['metadata'] === ['reason' => 'no heartbeat'],
        );
        $this->assertSame(0, $this->service->sweepStale(300), 'A swept row is terminal and not swept again.');
    }
}
