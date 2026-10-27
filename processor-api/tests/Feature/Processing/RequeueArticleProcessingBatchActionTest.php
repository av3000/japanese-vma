<?php

declare(strict_types=1);

namespace Tests\Feature\Processing;

use App\Application\Articles\Actions\Processing\RequeueArticleProcessingBatchAction;
use App\Application\Articles\Jobs\ProcessArticleContentJob;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Processing\DTOs\ProcessingRetryBatchDTO;
use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\Enums\ProcessingStatus;
use App\Domain\Processing\Enums\ProcessingTaskType;
use App\Infrastructure\Auth\Providers\PassportCurrentUserProvider;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\ProcessingState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Feature\Filament\Concerns\InteractsWithAdminPanel;
use Tests\TestCase;

/**
 * Retrying a selection: refused as a whole over the cap, running runs skipped, the rest queued or
 * reported as failed one by one.
 */
final class RequeueArticleProcessingBatchActionTest extends TestCase
{
    use InteractsWithAdminPanel;
    use RefreshDatabase;

    private AuthenticatedUser $operator;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        $this->seedAdminPanel();
        $this->operator = PassportCurrentUserProvider::authenticatedUserFor($this->createAdmin());
    }

    public function test_a_selection_over_the_cap_is_refused_as_a_whole(): void
    {
        $runs = array_map(
            static fn (int $index): array => ['entityType' => ProcessingEntityType::Article, 'entityId' => (string) Str::uuid(), 'label' => "Run {$index}"],
            range(1, RequeueArticleProcessingBatchAction::MAX_BATCH + 1),
        );

        $result = app(RequeueArticleProcessingBatchAction::class)->execute($runs, $this->operator);

        self::assertSame('Articles.RetryBatchTooLarge', $result->getError()->code);
        Queue::assertNothingPushed();
    }

    public function test_a_run_still_reporting_progress_is_skipped_not_failed(): void
    {
        $running = $this->processingRun(ProcessingStatus::PROCESSING);

        $batch = $this->retry([$running]);

        self::assertSame(0, $batch->queued);
        self::assertSame([], $batch->failures);
        self::assertSame(['Running: still reporting progress'], $batch->skipped);
        Queue::assertNothingPushed();
    }

    public function test_a_mixed_selection_reports_each_outcome(): void
    {
        $failed = $this->processingRun(ProcessingStatus::FAILED);
        $missing = ['entityType' => ProcessingEntityType::Article, 'entityId' => (string) Str::uuid(), 'label' => 'Gone'];

        $batch = $this->retry([$failed, $missing]);

        self::assertSame(1, $batch->queued);
        self::assertCount(1, $batch->failures);
        self::assertStringStartsWith('Gone: ', $batch->failures[0]);
        self::assertSame([], $batch->skipped);
        Queue::assertPushed(ProcessArticleContentJob::class, 1);
    }

    public function test_article_processing_is_the_retryable_type(): void
    {
        self::assertTrue(ProcessingEntityType::Article->isRetryable());
    }

    /**
     * @param list<array{entityType: ProcessingEntityType, entityId: string, label: string}> $runs
     */
    private function retry(array $runs): ProcessingRetryBatchDTO
    {
        $result = app(RequeueArticleProcessingBatchAction::class)->execute($runs, $this->operator);

        self::assertTrue($result->isSuccess());

        return $result->getData();
    }

    /**
     * @return array{entityType: ProcessingEntityType, entityId: string, label: string}
     */
    private function processingRun(ProcessingStatus $status): array
    {
        $article = Article::factory()->create(['user_id' => $this->createPanelUser('Author '.Str::random(4))->id]);

        ProcessingState::query()->create([
            'entity_type' => ProcessingEntityType::Article,
            'entity_id' => $article->uuid,
            'task_type' => ProcessingTaskType::ArticleContentProcessing->value,
            'status' => $status,
            'attempt' => 1,
        ]);

        return [
            'entityType' => ProcessingEntityType::Article,
            'entityId' => (string) $article->uuid,
            'label' => $status === ProcessingStatus::PROCESSING ? 'Running' : 'Failed',
        ];
    }
}
