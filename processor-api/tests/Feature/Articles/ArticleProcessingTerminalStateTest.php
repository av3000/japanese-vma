<?php

declare(strict_types=1);

namespace Tests\Feature\Articles;

use App\Application\Articles\Jobs\ProcessArticleKanjisJob;
use App\Application\Articles\Jobs\ProcessArticleWordsJob;
use App\Application\JapaneseMaterial\Words\Services\WordAttachmentService;
use App\Application\JapaneseMaterial\Words\Services\WordExtractionServiceInterface;
use App\Application\LastOperations\Events\AsyncLastOperationStatusUpdated;
use App\Application\LastOperations\Services\LastOperationService;
use App\Domain\Shared\Enums\LastOperationStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Models\LastOperationState;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Contracts\Queue\Job as QueueJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

/**
 * Issue #245 (audit finding F-04): every article processing operation must reach a terminal
 * status, and a retry must not surface as a brand-new pending operation.
 */
class ArticleProcessingTerminalStateTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
    }

    public function test_retry_reuses_the_failed_row_instead_of_creating_a_second_pending_row(): void
    {
        $article = $this->createArticle();
        $this->createWord('学校', 'がっこう');

        $failingExtraction = Mockery::mock(WordExtractionServiceInterface::class);
        $failingExtraction->shouldReceive('extractWordIds')->once()->andThrow(new RuntimeException('dictionary unavailable'));

        try {
            $this->wordsJob($article, attempt: 1)->handle(
                $failingExtraction,
                app(WordAttachmentService::class),
                app(LastOperationService::class),
            );
            $this->fail('The first attempt should rethrow so the worker can retry.');
        } catch (RuntimeException $exception) {
            $this->assertSame('dictionary unavailable', $exception->getMessage());
        }

        $rows = $this->operationRows($article->uuid, ProcessArticleWordsJob::TASK_TYPE);
        $this->assertCount(1, $rows);
        $firstRow = $rows->first();
        $this->assertSame(LastOperationStatus::FAILED, $firstRow->status);
        $this->assertSame(1, $firstRow->metadata['attempts']);
        $this->assertSame('dictionary unavailable', $firstRow->metadata['error']);

        $this->wordsJob($article, attempt: 2)->handle(
            app(WordExtractionServiceInterface::class),
            app(WordAttachmentService::class),
            app(LastOperationService::class),
        );

        $rows = $this->operationRows($article->uuid, ProcessArticleWordsJob::TASK_TYPE);
        $this->assertCount(1, $rows, 'A retry must not insert a second last_operations row.');
        $retriedRow = $rows->first();
        $this->assertSame($firstRow->id, $retriedRow->id);
        $this->assertSame(LastOperationStatus::COMPLETED, $retriedRow->status);
        $this->assertSame(2, $retriedRow->metadata['attempts']);
    }

    public function test_failed_hook_marks_the_attempts_row_failed_and_broadcasts(): void
    {
        $article = $this->createArticle();
        $job = $this->wordsJob($article, attempt: 3);
        $row = app(LastOperationService::class)->beginAttempt(
            new EntityId($article->uuid),
            'article',
            ProcessArticleWordsJob::TASK_TYPE,
            3,
        );
        $job->operationStateId = $row->id;

        Event::fake([AsyncLastOperationStatusUpdated::class]);

        $job->failed(new RuntimeException("Job exceeded\n   the 120 second timeout"));

        $row->refresh();
        $this->assertSame(LastOperationStatus::FAILED, $row->status);
        $this->assertSame('Job exceeded the 120 second timeout', $row->metadata['error']);
        $this->assertSame(RuntimeException::class, $row->metadata['exception']);
        $this->assertSame(3, $row->metadata['attempts']);

        Event::assertDispatched(
            AsyncLastOperationStatusUpdated::class,
            fn (AsyncLastOperationStatusUpdated $event): bool => $event->operationState->is($row)
                && $event->operationState->status === LastOperationStatus::FAILED,
        );
    }

    public function test_failed_hook_finds_the_latest_non_terminal_row_when_handle_never_ran(): void
    {
        $article = $this->createArticle();

        $completed = $this->insertOperation($article->uuid, ProcessArticleKanjisJob::TASK_TYPE, LastOperationStatus::COMPLETED);
        $pending = $this->insertOperation($article->uuid, ProcessArticleKanjisJob::TASK_TYPE, LastOperationStatus::PENDING);

        $job = new ProcessArticleKanjisJob($article->uuid, $article->content_jp);
        $job->failed(new RuntimeException('killed before handle()'));

        $this->assertSame(LastOperationStatus::FAILED, $pending->refresh()->status);
        $this->assertSame('killed before handle()', $pending->metadata['error']);
        $this->assertSame(LastOperationStatus::COMPLETED, $completed->refresh()->status, 'Terminal rows must not be touched.');
    }

    public function test_failed_hook_is_a_no_op_when_no_operation_row_exists(): void
    {
        $job = new ProcessArticleWordsJob((string) Str::uuid(), '学校');

        $job->failed(new RuntimeException('nothing to mark'));

        $this->assertSame(0, LastOperationState::count());
    }

    private function wordsJob(PersistenceArticle $article, int $attempt): ProcessArticleWordsJob
    {
        $job = new ProcessArticleWordsJob($article->uuid, $article->title_jp.$article->content_jp);

        $queueJob = Mockery::mock(QueueJob::class);
        $queueJob->shouldReceive('attempts')->andReturn($attempt);
        $job->setJob($queueJob);

        return $job;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, LastOperationState>
     */
    private function operationRows(string $articleUuid, string $taskType)
    {
        return LastOperationState::query()
            ->where('processable_id', $articleUuid)
            ->where('task_type', $taskType)
            ->orderBy('id')
            ->get();
    }

    private function insertOperation(string $articleUuid, string $taskType, LastOperationStatus $status): LastOperationState
    {
        return LastOperationState::create([
            'processable_id' => $articleUuid,
            'processable_type' => PersistenceArticle::class,
            'task_type' => $taskType,
            'status' => $status,
            'metadata' => [],
        ]);
    }

    private function createArticle(): PersistenceArticle
    {
        return PersistenceArticle::factory()->byUser(User::factory()->create())->create([
            'title_jp' => '学校',
            'content_jp' => '勉強します。日本語の本文です。',
        ]);
    }

    private function createWord(string $word, string $furigana): int
    {
        return DB::table('japanese_word_bank_long')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'entry_sequence' => (string) random_int(100000, 999999),
            'word' => $word,
            'furigana' => $furigana,
            'jlpt' => 'N5',
            'word_type' => 'noun',
            'word_k_ele' => $word,
            'furigana_r_ele' => $furigana,
            'sense' => 'study',
        ]);
    }
}
