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
use Illuminate\Support\Facades\Log;

class ProcessArticleKanjisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        private readonly string $articleUuid,
        private readonly string $articleContentJp
    ) {}

    public function handle(
        KanjiExtractionService $kanjiExtractionService,
        KanjiAttachmentService $kanjiAttachmentService
    ): void {
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
                // If you want to retry the job on specific failures, throw an exception
                throw new \RuntimeException($result->getError()->description);
            }

            Log::info('Successfully processed and attached kanjis for article', [
                'article_uuid' => $this->articleUuid,
                'kanji_count' => count($result->getData()),
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing article kanjis in job', [
                'article_uuid' => $this->articleUuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Re-throw to make Laravel retry the job if configured, or move to failed jobs
            throw $e;
        }
    }
}
