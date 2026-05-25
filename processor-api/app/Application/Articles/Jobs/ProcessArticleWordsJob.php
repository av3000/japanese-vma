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

    public int $tries = 3;

    public int $timeout = 120;

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
        $operationState = $lastOperationService->startOperation(
            new EntityId($this->articleUuid),
            'article',
            'words_extraction',
        );
        $operationStateId = $operationState->id;

        $lastOperationService->updateStatus(
            $operationStateId,
            LastOperationStatus::PROCESSING
        );

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
}
