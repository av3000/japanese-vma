<?php

declare(strict_types=1);

namespace App\Application\Articles\Jobs;

use App\Application\Articles\Actions\Processing\CalculateJlptLevelsAction;
use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Application\JapaneseMaterial\Kanjis\Interfaces\Repositories\KanjiRepositoryInterface;
use App\Application\JapaneseMaterial\Kanjis\Services\KanjiExtractionService;
use App\Application\JapaneseMaterial\Words\Services\WordExtractionServiceInterface;
use App\Application\Processing\Services\ProcessingStateServiceInterface;
use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\KanjiCharacter;
use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\Enums\ProcessingTaskType;
use App\Domain\Shared\ValueObjects\EntityId;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * The one background job behind an article's Japanese content (ADR 0001, issue #257).
 *
 * Extracts kanji from `content_jp`, words from `title_jp` + `content_jp`, computes the JLPT
 * counters, and persists all three in one transaction. The job carries only the article uuid
 * and the content version it was queued for: it re-reads the text at run time, and if the
 * article has moved on it marks its row `superseded` and touches nothing.
 */
class ProcessArticleContentJob implements ShouldQueue, ShouldQueueAfterCommit
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public const TASK = ProcessingTaskType::ArticleContentProcessing;

    public const STAGE_KANJI = 'kanji';

    public const STAGE_WORDS = 'words';

    public const STAGE_PERSIST = 'persist';

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 30, 90];

    public int $timeout = 120;

    public function __construct(
        public readonly string $articleUuid,
        public readonly int $contentVersion,
    ) {
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        // Two edits in quick succession queue two jobs; the second waits for the first instead
        // of racing it, and a wedged lock expires before retry_after would re-deliver.
        return [(new WithoutOverlapping($this->articleUuid))->releaseAfter(30)->expireAfter(180)];
    }

    public function handle(
        ArticleRepositoryInterface $articles,
        KanjiExtractionService $kanjiExtraction,
        KanjiRepositoryInterface $kanjis,
        WordExtractionServiceInterface $wordExtraction,
        CalculateJlptLevelsAction $calculateJlptLevels,
        ProcessingStateServiceInterface $states,
    ): void {
        $entityId = new EntityId($this->articleUuid);

        $source = $articles->findProcessingSource($entityId);

        if ($source === null) {
            $states->markFailed(ProcessingEntityType::Article, $entityId, self::TASK, 'article_not_found', 'Article not found');

            throw new RuntimeException("Article not found: {$this->articleUuid}");
        }

        if ($source->contentVersion !== $this->contentVersion) {
            Log::info('Article content processing superseded', [
                'article_uuid' => $this->articleUuid,
                'queued_version' => $this->contentVersion,
                'current_version' => $source->contentVersion,
            ]);

            $states->markSuperseded(ProcessingEntityType::Article, $entityId, self::TASK, $this->contentVersion);

            return;
        }

        $states->markProcessing(ProcessingEntityType::Article, $entityId, self::TASK, $this->attempts());

        $stage = self::STAGE_KANJI;

        try {
            $characters = $kanjiExtraction->extractUniqueKanjis($source->contentJp);
            /** @var Kanji[] $resolvedKanjis */
            $resolvedKanjis = $characters === []
                ? []
                : $kanjis->findManyByCharacters(array_map(fn (string $char) => new KanjiCharacter($char), $characters));
            $kanjiIds = array_map(fn (Kanji $kanji) => $kanji->getIdValue(), $resolvedKanjis);
            $jlptLevels = $calculateJlptLevels->execute($resolvedKanjis);

            $stage = self::STAGE_WORDS;
            $wordIds = array_values(array_unique($wordExtraction->extractWordIds($source->wordExtractionText())));

            $stage = self::STAGE_PERSIST;
            $articles->syncContentProcessing($source->id, $kanjiIds, $wordIds, $jlptLevels);
        } catch (Throwable $exception) {
            Log::error('Article content processing failed', [
                'article_uuid' => $this->articleUuid,
                'content_version' => $this->contentVersion,
                'stage' => $stage,
                'attempt' => $this->attempts(),
                'error' => $exception->getMessage(),
            ]);

            $states->markFailed(ProcessingEntityType::Article, $entityId, self::TASK, $stage, $exception, ['stage' => $stage]);

            throw $exception;
        }

        $states->markCompleted(ProcessingEntityType::Article, $entityId, self::TASK, [
            'kanji_count' => count($kanjiIds),
            'word_count' => count($wordIds),
        ]);

        Log::info('Article content processing completed', [
            'article_uuid' => $this->articleUuid,
            'content_version' => $this->contentVersion,
            'kanji_count' => count($kanjiIds),
            'word_count' => count($wordIds),
        ]);
    }

    /**
     * Called by the worker once the job will not run again: after the last attempt, or when an
     * attempt was killed (timeout, OOM, restart) rather than throwing. Guarantees a terminal row.
     */
    public function failed(Throwable $exception): void
    {
        app(ProcessingStateServiceInterface::class)->recordFailure(
            ProcessingEntityType::Article,
            new EntityId($this->articleUuid),
            self::TASK,
            $exception,
            $this->attempts(),
        );
    }
}
