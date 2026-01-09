<?php

declare(strict_types=1);

namespace App\Application\Articles\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Application\JapaneseMaterial\Kanjis\Services\{KanjiExtractionService, KanjiAttachmentService};
use App\Application\LastOperations\Services\LastOperationService;
use App\Domain\Shared\Enums\LastOperationStatus;
use Illuminate\Support\Facades\Log;

class ProcessArticleKanjisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        private readonly string $articleUuid,
        private readonly string $articleContentJp,
        private readonly int $operationStateId
    ) {}

    public function handle(
        KanjiExtractionService $kanjiExtractionService,
        KanjiAttachmentService $kanjiAttachmentService,
        LastOperationService $lastOperationService
    ): void {

        $lastOperationService->updateStatus(
            $this->operationStateId,
            LastOperationStatus::PROCESSING
        );

        try {
            $uniqueKanjiCharacters = $kanjiExtractionService->extractUniqueKanjis($this->articleContentJp);

            $result = $kanjiAttachmentService->attachKanjisToArticle(
                new EntityId($this->articleUuid),
                $uniqueKanjiCharacters
            );

            if ($result->isFailure()) {
                Log::error('Failed to attach kanjis to article via job', [
                    'article_uuid' => $this->articleUuid,
                    'error' => $result->getError()->description,
                ]);

                $lastOperationService->updateStatus(
                    $this->operationStateId,
                    LastOperationStatus::FAILED,
                    ['error' => $result->getError()->description]
                );
                // If you want to retry the job on specific failures, throw an exception
                throw new \RuntimeException($result->getError()->description);
            }

            $kanjiCount = count($result->getData());

            Log::info('Successfully processed and attached kanjis for article', [
                'article_uuid' => $this->articleUuid,
                'kanji_count' => $kanjiCount,
            ]);


            $lastOperationService->updateStatus(
                $this->operationStateId,
                LastOperationStatus::COMPLETED,
                [
                    'kanji_count' => $kanjiCount,
                    'message' => "Attached {$kanjiCount} kanjis."
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error processing article kanjis in job', [
                'article_uuid' => $this->articleUuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $lastOperationService->updateStatus(
                $this->operationStateId,
                LastOperationStatus::FAILED,
                ['error' => $e->getMessage()]
            );
            // Re-throw to make Laravel retry the job if configured, or move to failed jobs
            throw $e;
        }
    }
}
