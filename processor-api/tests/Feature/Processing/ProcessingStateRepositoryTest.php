<?php

declare(strict_types=1);

namespace Tests\Feature\Processing;

use App\Application\Processing\Interfaces\Repositories\ProcessingStateRepositoryInterface;
use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\Enums\ProcessingStatus;
use App\Domain\Processing\Enums\ProcessingTaskType;
use App\Domain\Processing\Exceptions\ProcessingStateNotFoundException;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\ProcessingState;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Issue #256 (ADR 0001): one current-state row per (entity, task), every transition in place.
 */
class ProcessingStateRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private const TASK = ProcessingTaskType::ArticleContentProcessing;

    private ProcessingStateRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(ProcessingStateRepositoryInterface::class);
    }

    public function test_start_or_reset_creates_then_resets_the_same_row(): void
    {
        $id = EntityId::from((string) Str::uuid());

        $first = $this->repository->startOrReset(ProcessingEntityType::Article, $id, self::TASK, 1);
        $this->repository->markProcessing(ProcessingEntityType::Article, $id, self::TASK, 2);
        $this->repository->markFailed(ProcessingEntityType::Article, $id, self::TASK, 'kanji', 'boom', ['stage' => 'kanji']);

        $second = $this->repository->startOrReset(ProcessingEntityType::Article, $id, self::TASK, 2);

        $this->assertSame($first->id, $second->id, 'The row is reused, never duplicated.');
        $this->assertSame(1, ProcessingState::count());
        $this->assertSame(ProcessingStatus::PENDING, $second->status);
        $this->assertSame(0, $second->attempt);
        $this->assertSame(2, $second->contentVersion);
        $this->assertNull($second->errorCode);
        $this->assertNull($second->errorMessage);
        $this->assertNull($second->startedAt);
        $this->assertNull($second->finishedAt);
        $this->assertSame([], $second->metadata);
    }

    public function test_the_entity_task_triple_is_unique_at_the_database(): void
    {
        $uuid = (string) Str::uuid();
        $row = ['entity_type' => 'article', 'entity_id' => $uuid, 'task_type' => self::TASK->value, 'content_version' => 1];
        DB::table('processing_states')->insert($row + ['created_at' => now(), 'updated_at' => now()]);

        $this->expectException(UniqueConstraintViolationException::class);

        DB::table('processing_states')->insert($row + ['created_at' => now(), 'updated_at' => now()]);
    }

    public function test_transitions_write_status_timing_and_error_columns(): void
    {
        $id = EntityId::from((string) Str::uuid());
        $this->repository->startOrReset(ProcessingEntityType::Article, $id, self::TASK, 1);

        $processing = $this->repository->markProcessing(ProcessingEntityType::Article, $id, self::TASK, 1);
        $this->assertSame(ProcessingStatus::PROCESSING, $processing?->status);
        $this->assertSame(1, $processing?->attempt);
        $this->assertNotNull($processing?->startedAt);
        $this->assertNull($processing?->finishedAt);

        $completed = $this->repository->markCompleted(ProcessingEntityType::Article, $id, self::TASK, ['kanji_count' => 2, 'word_count' => 5]);
        $this->assertSame(ProcessingStatus::COMPLETED, $completed?->status);
        $this->assertNotNull($completed?->finishedAt);
        $this->assertSame(['kanji_count' => 2, 'word_count' => 5], $completed?->metadata);

        $this->repository->startOrReset(ProcessingEntityType::Article, $id, self::TASK, 2);
        $failed = $this->repository->markFailed(ProcessingEntityType::Article, $id, self::TASK, 'words', str_repeat('x', 400), ['stage' => 'words']);
        $this->assertSame(ProcessingStatus::FAILED, $failed?->status);
        $this->assertSame('words', $failed?->errorCode);
        $this->assertSame(300, mb_strlen((string) $failed?->errorMessage), 'Error text is bounded at the column width.');
        $this->assertSame(['stage' => 'words'], $failed?->metadata);
    }

    public function test_transitions_throw_when_no_row_exists(): void
    {
        $id = EntityId::from((string) Str::uuid());

        // A read may legitimately find nothing; a write may not (#267).
        $this->assertNull($this->repository->getCurrent(ProcessingEntityType::Article, $id, self::TASK));

        $transitions = [
            fn () => $this->repository->markProcessing(ProcessingEntityType::Article, $id, self::TASK, 1),
            fn () => $this->repository->markCompleted(ProcessingEntityType::Article, $id, self::TASK, []),
            fn () => $this->repository->markFailed(ProcessingEntityType::Article, $id, self::TASK, 'x', 'y'),
            fn () => $this->repository->markSuperseded(ProcessingEntityType::Article, $id, self::TASK, 1),
        ];

        foreach ($transitions as $index => $transition) {
            try {
                $transition();
                $this->fail("Transition {$index} silently accepted a missing row.");
            } catch (ProcessingStateNotFoundException $exception) {
                $this->assertStringContainsString($id->value(), $exception->getMessage());
            }
        }
    }

    public function test_mark_superseded_only_touches_an_in_flight_row_for_that_version(): void
    {
        $id = EntityId::from((string) Str::uuid());
        $this->repository->startOrReset(ProcessingEntityType::Article, $id, self::TASK, 1);
        $this->repository->markProcessing(ProcessingEntityType::Article, $id, self::TASK, 1);

        $stale = $this->repository->markSuperseded(ProcessingEntityType::Article, $id, self::TASK, 1);
        $this->assertSame(ProcessingStatus::SUPERSEDED, $stale?->status);
        $this->assertNotNull($stale?->finishedAt);
        $this->assertTrue($stale?->isTerminal());

        // A newer version has since reset the row: a late "supersede v1" call must not touch it.
        $this->repository->startOrReset(ProcessingEntityType::Article, $id, self::TASK, 2);
        $this->assertNull($this->repository->markSuperseded(ProcessingEntityType::Article, $id, self::TASK, 1));
        $current = $this->repository->getCurrent(ProcessingEntityType::Article, $id, self::TASK);
        $this->assertSame(ProcessingStatus::PENDING, $current?->status);
        $this->assertSame(2, $current?->contentVersion);

        // Nor may it rewrite a run that already finished.
        $this->repository->markCompleted(ProcessingEntityType::Article, $id, self::TASK, []);
        $this->assertNull($this->repository->markSuperseded(ProcessingEntityType::Article, $id, self::TASK, 2));
        $this->assertSame(ProcessingStatus::COMPLETED, $this->repository->getCurrent(ProcessingEntityType::Article, $id, self::TASK)?->status);
    }

    public function test_get_current_batch_returns_one_row_per_entity_in_one_query(): void
    {
        $ids = [(string) Str::uuid(), (string) Str::uuid(), (string) Str::uuid()];
        foreach ([$ids[0], $ids[1]] as $uuid) {
            $this->repository->startOrReset(ProcessingEntityType::Article, EntityId::from($uuid), self::TASK, 1);
        }
        $this->repository->markCompleted(ProcessingEntityType::Article, EntityId::from($ids[1]), self::TASK, []);

        DB::enableQueryLog();
        $batch = $this->repository->getCurrentBatch(ProcessingEntityType::Article, $ids, self::TASK);
        $this->assertCount(1, DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertEqualsCanonicalizing([$ids[0], $ids[1]], array_keys($batch));
        $this->assertSame(ProcessingStatus::PENDING, $batch[$ids[0]]->status);
        $this->assertSame(ProcessingStatus::COMPLETED, $batch[$ids[1]]->status);
        $this->assertSame([], $this->repository->getCurrentBatch(ProcessingEntityType::Article, [], self::TASK));
    }

    public function test_get_stale_non_terminal_filters_by_status_and_age(): void
    {
        $stale = EntityId::from((string) Str::uuid());
        $fresh = EntityId::from((string) Str::uuid());
        $done = EntityId::from((string) Str::uuid());
        foreach ([$stale, $fresh, $done] as $id) {
            $this->repository->startOrReset(ProcessingEntityType::Article, $id, self::TASK, 1);
        }
        $this->repository->markProcessing(ProcessingEntityType::Article, $stale, self::TASK, 1);
        $this->repository->markCompleted(ProcessingEntityType::Article, $done, self::TASK, []);
        DB::table('processing_states')->whereIn('entity_id', [$stale->value(), $done->value()])->update(['updated_at' => now()->subMinutes(10)]);

        $result = $this->repository->getStaleNonTerminal(now()->subMinutes(5));

        $this->assertSame([$stale->value()], array_map(fn ($dto) => $dto->entityId, $result));
    }

    public function test_backfill_collapses_the_last_operations_history_into_one_row_per_article(): void
    {
        $article = (string) Str::uuid();
        $other = (string) Str::uuid();
        $t = fn (int $minutesAgo) => now()->subMinutes($minutesAgo);

        DB::table('last_operations')->insert([
            ['processable_id' => $article, 'processable_type' => 'article', 'task_type' => 'kanji_extraction', 'status' => 'pending', 'metadata' => json_encode(['attempts' => 1]), 'created_at' => $t(30), 'updated_at' => $t(30)],
            ['processable_id' => $article, 'processable_type' => 'article', 'task_type' => 'kanji_extraction', 'status' => 'failed', 'metadata' => json_encode(['attempts' => 2, 'error' => "Timeout\nafter 120 s"]), 'created_at' => $t(20), 'updated_at' => $t(19)],
            ['processable_id' => $article, 'processable_type' => 'article', 'task_type' => 'words_extraction', 'status' => 'completed', 'metadata' => json_encode(['attempts' => 1, 'word_count' => 7, 'message' => 'Attached 7 words.']), 'created_at' => $t(10), 'updated_at' => $t(9)],
            ['processable_id' => $other, 'processable_type' => 'article', 'task_type' => 'kanji_extraction', 'status' => 'processing', 'metadata' => json_encode([]), 'created_at' => $t(5), 'updated_at' => $t(5)],
        ]);
        DB::table('processing_states')->delete();

        // Re-run only the create/backfill migration against the seeded history.
        $migration = require database_path('migrations/2026_09_20_000000_create_processing_states_table.php');
        $migration->down();
        $migration->up();

        $rows = ProcessingState::query()->orderBy('entity_id')->get()->keyBy('entity_id');
        $this->assertCount(2, $rows, 'Three history rows for one article collapse into one; the other article gets its own.');

        $collapsed = $rows[$article];
        $this->assertSame('article_content_processing', $collapsed->task_type);
        $this->assertSame(ProcessingStatus::COMPLETED, $collapsed->status, 'The newest row wins.');
        $this->assertSame(1, $collapsed->attempt);
        $this->assertSame(1, $collapsed->content_version);
        $this->assertSame(['word_count' => 7], $collapsed->metadata, 'Only counts survive; free text does not.');
        $this->assertNotNull($collapsed->finished_at);

        $this->assertSame(ProcessingStatus::PROCESSING, $rows[$other]->status);
        $this->assertNull($rows[$other]->finished_at);

        $this->assertEqualsCanonicalizing(
            [$article, $other],
            array_keys($this->repository->getCurrentBatch(ProcessingEntityType::Article, [$article, $other], self::TASK)),
        );
    }
}
