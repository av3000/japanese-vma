<?php

declare(strict_types=1);

namespace App\Application\Articles\Jobs;

use App\Application\JapaneseMaterial\Words\Services\WordAttachmentService;
use App\Application\JapaneseMaterial\Words\Services\WordExtractionServiceInterface;
use App\Application\LastOperations\Services\LastOperationService;
use App\Domain\Shared\Enums\LastOperationStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ProcessArticleWordsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public const TASK_TYPE = 'words_extraction';

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * Id of the last_operations row this attempt is working on. Set before any work starts so
     * failed() can find it even when handle() is killed by a timeout or worker restart.
     */
    public ?int $operationStateId = null;

    public function __construct(
        private readonly string $articleUuid,
        private readonly string $articleText,
    ) {
    }

    public function handle(
        WordExtractionServiceInterface $wordExtractionService,
        WordAttachmentService $wordAttachmentService,
        LastOperationService $lastOperationService,
    ): void {
        $operationState = $lastOperationService->beginAttempt(
            new EntityId($this->articleUuid),
            'article',
            self::TASK_TYPE,
            $this->attempts(),
        );
        $this->operationStateId = $operationState->id;
        $operationStateId = $operationState->id;

        try {
            $wordIds = $wordExtractionService->extractWordIds($this->articleText);
            $result = $wordAttachmentService->attachWordsToArticle(
                new EntityId($this->articleUuid),
                $wordIds,
            );

            if ($result->isFailure()) {
                $lastOperationService->updateStatus(
                    $operationStateId,
                    LastOperationStatus::FAILED,
                    ['error' => $result->getError()->description],
                );

                throw new RuntimeException($result->getError()->description);
            }

            $attachedWordIds = $result->getData();
            $wordCount = is_array($attachedWordIds) ? count($attachedWordIds) : 0;

            Log::info('Successfully processed and attached words for article', [
                'article_uuid' => $this->articleUuid,
                'word_count' => $wordCount,
            ]);

            $lastOperationService->updateStatus(
                $operationStateId,
                LastOperationStatus::COMPLETED,
                [
                    'word_count' => $wordCount,
                    'message' => "Attached {$wordCount} words.",
                ],
            );
        } catch (Throwable $e) {
            Log::error('Error processing article words in job', [
                'article_uuid' => $this->articleUuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $lastOperationService->updateStatus(
                $operationStateId,
                LastOperationStatus::FAILED,
                ['error' => $e->getMessage()],
            );

            throw $e;
        }
    }

    /**
     * Called by the worker once the job will not be retried again: after the last attempt, or
     * when the attempt was killed (timeout, out of memory, worker restart) rather than throwing.
     * Guarantees the operation row reaches a terminal status. The exception is not swallowed;
     * the worker has already recorded it in failed_jobs.
     */
    public function failed(Throwable $exception): void
    {
        app(LastOperationService::class)->recordFailure(
            new EntityId($this->articleUuid),
            self::TASK_TYPE,
            $this->operationStateId,
            $exception,
            $this->attempts(),
        );
    }
}
