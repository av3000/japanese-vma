<?php

declare(strict_types=1);

namespace Tests\Unit\Processing;

use App\Application\Processing\Events\ProcessingStatusUpdated;
use App\Application\Processing\Services\ProcessingStateService;
use App\Application\Processing\Services\ProcessingStateServiceInterface;
use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\Enums\ProcessingStatus;
use App\Domain\Processing\Enums\ProcessingTaskType;
use App\Domain\Processing\Exceptions\ProcessingStateNotFoundException;
use App\Domain\Shared\ValueObjects\EntityId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

/**
 * Every write through the service reaches clients exactly once as a snapshot of what was
 * just written (audit F-08 kept intact across the ADR 0001 table change).
 */
class ProcessingStateServiceTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    private const TASK = ProcessingTaskType::ArticleContentProcessing;

    private ProcessingStateServiceInterface $service;

    private EntityId $id;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
        $this->service = app(ProcessingStateServiceInterface::class);
        $this->id = EntityId::from((string) Str::uuid());
        Event::fake([ProcessingStatusUpdated::class]);
    }

    public function test_a_transition_is_pushed_on_the_article_channel_and_the_owner_channel(): void
    {
        $owner = \App\Infrastructure\Persistence\Models\User::factory()->create();
        $article = \App\Infrastructure\Persistence\Models\Article::factory()->byUser($owner)->create([
            'title_jp' => '学校の話',
            'content_jp' => '学校で勉強します。日本語の本文です。',
        ]);
        $id = EntityId::from($article->uuid);

        $this->service->startOrReset(ProcessingEntityType::Article, $id, self::TASK, 1);
        $this->service->markProcessing(ProcessingEntityType::Article, $id, self::TASK, 1);

        Event::assertDispatched(
            ProcessingStatusUpdated::class,
            fn (ProcessingStatusUpdated $event): bool => array_map('strval', $event->broadcastOn()) === [
                "private-processing_states.{$article->uuid}",
                "private-App.User.{$owner->uuid}",
            ] && $event->snapshot['entity_id'] === $article->uuid
                && $event->snapshot['attempt'] === 1,
        );
    }

    public function test_an_entity_without_a_resolvable_owner_is_pushed_on_the_article_channel_only(): void
    {
        $this->service->startOrReset(ProcessingEntityType::Article, $this->id, self::TASK, 1);

        Event::assertDispatched(
            ProcessingStatusUpdated::class,
            fn (ProcessingStatusUpdated $event): bool => count($event->broadcastOn()) === 1,
        );
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

        Event::assertDispatchedTimes(ProcessingStatusUpdated::class, 3);

        $statuses = [];
        Event::assertDispatched(ProcessingStatusUpdated::class, function (ProcessingStatusUpdated $event) use (&$statuses): bool {
            $statuses[] = $event->status();

            return $event->entityUuid === $this->id->value()
                && $event->snapshot['type'] === self::TASK->value;
        });
        $this->assertSame(
            [ProcessingStatus::PENDING, ProcessingStatus::PROCESSING, ProcessingStatus::COMPLETED],
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

        $this->assertSame(ProcessingStatus::FAILED, $state?->status);
        $this->assertSame('kanji', $state?->errorCode);
        $this->assertSame('Job exceeded the 120 second timeout', $state?->errorMessage);
        $this->assertSame(['stage' => 'kanji', 'exception' => RuntimeException::class], $state?->metadata);
    }

    public function test_database_exceptions_never_leak_sql_into_the_payload(): void
    {
        $this->service->startOrReset(ProcessingEntityType::Article, $this->id, self::TASK, 1);

        $query = new \Illuminate\Database\QueryException(
            'pgsql',
            'insert into "article_kanji" ("article_id", "kanji_id") values (?, ?)',
            [7, 9],
            new \PDOException('SQLSTATE[23505]: Unique violation'),
        );

        $state = $this->service->markFailed(ProcessingEntityType::Article, $this->id, self::TASK, 'persist', $query, ['stage' => 'persist']);

        $this->assertSame('Database error', $state?->errorMessage);
        Event::assertDispatched(
            ProcessingStatusUpdated::class,
            function (ProcessingStatusUpdated $event): bool {
                $json = json_encode($event->broadcastWith(), JSON_THROW_ON_ERROR);

                return $event->status() === ProcessingStatus::FAILED
                    && ! str_contains($json, 'insert into')
                    && ! str_contains($json, 'SQLSTATE')
                    && ! str_contains($json, '#0 ');
            },
        );
    }

    public function test_a_trailing_sql_fragment_is_stripped_from_other_exceptions(): void
    {
        $this->service->startOrReset(ProcessingEntityType::Article, $this->id, self::TASK, 1);

        $state = $this->service->markFailed(
            ProcessingEntityType::Article,
            $this->id,
            self::TASK,
            'words',
            new RuntimeException("Lookup failed (SQL: select * from japanese_word_bank_long where word like '学%')"),
        );

        $this->assertSame('Lookup failed', $state?->errorMessage);
    }

    public function test_a_transition_on_an_unknown_row_throws_instead_of_losing_the_update(): void
    {
        $this->expectException(ProcessingStateNotFoundException::class);
        $this->expectExceptionMessage($this->id->value());

        $this->service->markProcessing(ProcessingEntityType::Article, $this->id, self::TASK, 1);
    }

    /**
     * @return list<array{string, callable(ProcessingStateServiceInterface, EntityId): mixed}>
     */
    public static function unknownRowTransitions(): array
    {
        $task = self::TASK;

        return [
            'markProcessing' => [fn (ProcessingStateServiceInterface $service, EntityId $id) => $service
                ->markProcessing(ProcessingEntityType::Article, $id, $task, 1)],
            'markCompleted' => [fn (ProcessingStateServiceInterface $service, EntityId $id) => $service
                ->markCompleted(ProcessingEntityType::Article, $id, $task, [])],
            'markFailed' => [fn (ProcessingStateServiceInterface $service, EntityId $id) => $service
                ->markFailed(ProcessingEntityType::Article, $id, $task, 'boom', 'boom')],
            'markSuperseded' => [fn (ProcessingStateServiceInterface $service, EntityId $id) => $service
                ->markSuperseded(ProcessingEntityType::Article, $id, $task, 1)],
        ];
    }

    #[DataProvider('unknownRowTransitions')]
    public function test_every_transition_on_an_unknown_row_throws(callable $transition): void
    {
        $this->expectException(ProcessingStateNotFoundException::class);

        $transition($this->service, $this->id);
    }

    public function test_nothing_to_supersede_is_a_silent_no_op_rather_than_an_error(): void
    {
        // The row exists but already finished: a late superseded run has nothing to write, and
        // that is not the missing-row case #267 made loud.
        $this->service->startOrReset(ProcessingEntityType::Article, $this->id, self::TASK, 1);
        $this->service->markCompleted(ProcessingEntityType::Article, $this->id, self::TASK, []);

        $this->assertNull($this->service->markSuperseded(ProcessingEntityType::Article, $this->id, self::TASK, 1));

        Event::assertDispatchedTimes(ProcessingStatusUpdated::class, 2);
    }

    public function test_metadata_from_a_failed_attempt_does_not_survive_into_the_completed_state(): void
    {
        $this->service->startOrReset(ProcessingEntityType::Article, $this->id, self::TASK, 1);
        $this->service->markFailed(ProcessingEntityType::Article, $this->id, self::TASK, 'words', 'boom', ['stage' => 'words']);

        $completed = $this->service->markCompleted(ProcessingEntityType::Article, $this->id, self::TASK, [
            'kanji_count' => 3,
            'word_count' => 7,
        ]);

        $this->assertSame(['kanji_count' => 3, 'word_count' => 7], $completed->metadata);
        $this->assertArrayNotHasKey('stage', (array) $completed->metadata);
        $this->assertArrayNotHasKey('exception', (array) $completed->metadata);
        $this->assertNull($completed->errorCode);
        $this->assertNull($completed->errorMessage);

        Event::assertDispatched(
            ProcessingStatusUpdated::class,
            fn (ProcessingStatusUpdated $event): bool => $event->status() === ProcessingStatus::COMPLETED
                && (array) $event->snapshot['metadata'] === ['kanji_count' => 3, 'word_count' => 7],
        );
    }

    public function test_record_failure_fails_only_a_non_terminal_row(): void
    {
        $this->service->recordFailure(ProcessingEntityType::Article, $this->id, self::TASK, new RuntimeException('no row'), 1);
        Event::assertNotDispatched(ProcessingStatusUpdated::class);

        $this->service->startOrReset(ProcessingEntityType::Article, $this->id, self::TASK, 1);
        $this->service->recordFailure(ProcessingEntityType::Article, $this->id, self::TASK, new RuntimeException('killed'), 3);
        Event::assertDispatched(
            ProcessingStatusUpdated::class,
            fn (ProcessingStatusUpdated $event): bool => $event->status() === ProcessingStatus::FAILED,
        );

        $this->service->startOrReset(ProcessingEntityType::Article, $this->id, self::TASK, 2);
        $this->service->markCompleted(ProcessingEntityType::Article, $this->id, self::TASK, []);
        Event::assertDispatchedTimes(ProcessingStatusUpdated::class, 4);

        $this->service->recordFailure(ProcessingEntityType::Article, $this->id, self::TASK, new RuntimeException('late'), 3);
        Event::assertDispatchedTimes(ProcessingStatusUpdated::class, 4, 'A completed row is left alone.');
    }

    public function test_sequence_strictly_increases_across_the_lifecycle_of_one_row(): void
    {
        $sequences = [];

        $sequences[] = $this->service->startOrReset(ProcessingEntityType::Article, $this->id, self::TASK, 1)->sequence;
        $sequences[] = $this->service->markProcessing(ProcessingEntityType::Article, $this->id, self::TASK, 1)->sequence;
        $sequences[] = $this->service->markCompleted(ProcessingEntityType::Article, $this->id, self::TASK, [])->sequence;
        // A second run of the same row continues the counter instead of restarting it.
        $sequences[] = $this->service->startOrReset(ProcessingEntityType::Article, $this->id, self::TASK, 2)->sequence;
        $sequences[] = $this->service->markProcessing(ProcessingEntityType::Article, $this->id, self::TASK, 1)->sequence;

        $sorted = $sequences;
        sort($sorted);

        $this->assertSame($sorted, $sequences, 'Sequence must never go backwards.');
        $this->assertSame(count($sequences), count(array_unique($sequences)), 'Sequence must never repeat.');

        Event::assertDispatched(
            ProcessingStatusUpdated::class,
            fn (ProcessingStatusUpdated $event): bool => $event->snapshot['sequence'] === end($sequences),
        );
    }

    public function test_sweep_stale_fails_old_non_terminal_rows_with_the_stale_code(): void
    {
        $this->service->startOrReset(ProcessingEntityType::Article, $this->id, self::TASK, 1);
        \Illuminate\Support\Facades\DB::table('processing_states')->update(['updated_at' => now()->subMinutes(10)]);

        $this->assertSame(1, $this->service->sweepStale(300));

        Event::assertDispatched(
            ProcessingStatusUpdated::class,
            fn (ProcessingStatusUpdated $event): bool => $event->status() === ProcessingStatus::FAILED
                && (array) $event->snapshot['metadata'] === ['reason' => 'no heartbeat'],
        );
        $this->assertSame(0, $this->service->sweepStale(300), 'A swept row is terminal and not swept again.');
    }
}
