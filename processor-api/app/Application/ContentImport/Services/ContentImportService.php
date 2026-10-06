<?php

declare(strict_types=1);

namespace App\Application\ContentImport\Services;

use App\Application\Articles\Services\ArticleServiceInterface;
use App\Application\ContentImport\Interfaces\ArticleTaggerInterface;
use App\Application\ContentImport\Interfaces\ContentSourceAdapterRegistryInterface;
use App\Application\ContentImport\Interfaces\Providers\ImportSettingsProviderInterface;
use App\Application\ContentImport\Interfaces\Providers\SystemAuthorProviderInterface;
use App\Application\ContentImport\Interfaces\Repositories\ContentSourceRepositoryInterface;
use App\Application\ContentImport\Interfaces\Repositories\ImportRunRepositoryInterface;
use App\Domain\Articles\DTOs\ArticleCreateDTO;
use App\Domain\Articles\Errors\ArticleErrors;
use App\Domain\Articles\ValueObjects\ArticleAuthor;
use App\Domain\Articles\ValueObjects\ArticleProvenance;
use App\Domain\ContentImport\DTOs\ContentSourceDTO;
use App\Domain\ContentImport\DTOs\ExternalArticle;
use App\Domain\ContentImport\DTOs\ImportItemResult;
use App\Domain\ContentImport\DTOs\ImportRunResult;
use App\Domain\ContentImport\Enums\ImportItemOutcome;
use App\Domain\ContentImport\Enums\ImportRunStatus;
use App\Domain\ContentImport\Errors\ContentImportErrors;
use App\Domain\ContentImport\Exceptions\ContentSourceUnavailableException;
use App\Domain\ContentImport\ValueObjects\ImportRunSettings;
use App\Shared\Results\Result;
use DateTimeImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * One Import Run: list what the source has that is not imported yet, filter it, tag it, and
 * create each survivor as a public Imported Article through the ordinary article creation path.
 *
 * Volume is bounded twice: by a cap on articles created per run, and by a ceiling on how many
 * listed articles one run may look at, so a source full of filtered items cannot page forever.
 * Articles already imported are skipped rather than ending the listing, so one that failed is
 * tried again by the next run while the source still lists it.
 */
class ContentImportService implements ContentImportServiceInterface
{
    /** A run row still `running` after this long belongs to a process that died. */
    private const ABANDONED_AFTER = '-2 hours';

    public function __construct(
        private readonly ContentSourceRepositoryInterface $sources,
        private readonly ContentSourceAdapterRegistryInterface $adapters,
        private readonly ImportSettingsProviderInterface $settings,
        private readonly ImportRunRepositoryInterface $runs,
        private readonly ArticleTaggerInterface $tagger,
        private readonly SystemAuthorProviderInterface $systemAuthor,
        private readonly ArticleServiceInterface $articles,
    ) {
    }

    public function enabledSourceKeys(): array
    {
        return $this->sources->enabledKeys();
    }

    public function run(string $sourceKey, bool $dryRun = false): Result
    {
        $source = $this->sources->findByKey($sourceKey);
        $adapter = $this->adapters->for($sourceKey);

        if ($source === null || $adapter === null) {
            return Result::failure(ContentImportErrors::unknownSource($sourceKey));
        }

        if (! $source->enabled) {
            return Result::failure(ContentImportErrors::sourceDisabled($sourceKey));
        }

        $author = $this->systemAuthor->author();

        if ($author === null) {
            return Result::failure(ContentImportErrors::systemAuthorMissing());
        }

        $settings = $this->settings->for($sourceKey);
        $runId = null;

        if (! $dryRun) {
            $this->closeAbandonedRuns($source);
            $runId = $this->runs->start($source->id);
        }

        $items = [];
        $created = 0;
        $status = ImportRunStatus::Succeeded;
        $error = null;

        try {
            $listing = $adapter->listRecent(
                fn (string $externalId): bool => $this->articles->hasImportedArticle($source->id, $externalId),
            );

            // Checked before asking for the next item, so the lazy listing is never asked for one
            // it will not use.
            if (! $settings->isLimitReached($created, count($items))) {
                foreach ($listing as $article) {
                    $item = $this->importOne($source, $article, $author, $settings, $dryRun);
                    $items[] = $item;

                    if ($item->outcome === ImportItemOutcome::Created || $item->outcome === ImportItemOutcome::WouldCreate) {
                        $created++;
                    }

                    if ($settings->isLimitReached($created, count($items))) {
                        break;
                    }
                }
            }
        } catch (ContentSourceUnavailableException $e) {
            $status = ImportRunStatus::Failed;
            $error = $e->getMessage();

            Log::warning('Content import run failed', ['source' => $sourceKey, 'error' => $error]);
        } catch (Throwable $e) {
            // Anything else is a bug, not an unreadable source, but the run row must still be
            // closed: a row left `running` would hide every later run's state.
            $status = ImportRunStatus::Failed;
            $error = $e::class.': '.$e->getMessage();

            Log::error('Content import run crashed', ['source' => $sourceKey, 'exception' => $e]);
        }

        $result = new ImportRunResult($sourceKey, $status, $dryRun, $items, $error);

        if ($runId === null) {
            return Result::success($result);
        }

        $this->runs->finish($runId, $result);

        return Result::success(new ImportRunResult(
            $sourceKey,
            $status,
            $dryRun,
            $items,
            $error,
            $this->hasStalled($source, $settings->stalledAfterRuns),
        ));
    }

    /**
     * A process killed mid-run (deploy, out of memory, timeout) never closes its row. Such rows
     * are closed as failed before the next run starts, so the run history stays truthful.
     */
    private function closeAbandonedRuns(ContentSourceDTO $source): void
    {
        $closed = $this->runs->closeAbandoned($source->id, new DateTimeImmutable(self::ABANDONED_AFTER));

        if ($closed > 0) {
            Log::warning('Content import closed runs that never finished', [
                'source' => $source->key,
                'runs' => $closed,
            ]);
        }
    }

    /**
     * An unofficial source rarely breaks with an error; it breaks by quietly yielding nothing.
     * Several successful runs in a row that created nothing are reported as a warning.
     */
    private function hasStalled(ContentSourceDTO $source, int $runs): bool
    {
        if ($runs < 1) {
            return false;
        }

        $counts = $this->runs->recentCreatedCounts($source->id, $runs);

        if (count($counts) < $runs || array_sum($counts) > 0) {
            return false;
        }

        Log::warning('Content import has created nothing for several runs', [
            'source' => $source->key,
            'runs' => $runs,
        ]);

        return true;
    }

    private function importOne(
        ContentSourceDTO $source,
        ExternalArticle $article,
        ArticleAuthor $author,
        ImportRunSettings $settings,
        bool $dryRun,
    ): ImportItemResult {
        $skip = fn (ImportItemOutcome $outcome, ?string $detail = null): ImportItemResult => new ImportItemResult(
            $article->externalId,
            $article->title,
            $outcome,
            detail: $detail,
        );

        if ($settings->excludesEveryGenre($article->genres)) {
            return $skip(ImportItemOutcome::FilteredGenre, implode(', ', $article->genres));
        }

        if ($settings->isLeadTooShort($article->lead)) {
            return $skip(ImportItemOutcome::FilteredTooShort);
        }

        try {
            $tags = $this->tagger->tagsFor($source, $article);

            if ($dryRun) {
                return new ImportItemResult($article->externalId, $article->title, ImportItemOutcome::WouldCreate, $tags);
            }

            $result = $this->articles->createArticle(new ArticleCreateDTO(
                title_jp: $article->title,
                title_en: null,
                content_jp: trim($article->lead),
                content_en: null,
                source_link: $article->canonicalUrl,
                publicity: true,
                tags: $tags === [] ? null : $tags,
                provenance: ArticleProvenance::imported($source->id, $article->externalId),
            ), $author);
        } catch (Throwable $e) {
            return $this->failed($source, $article, $e->getMessage());
        }

        if ($result->isSuccess()) {
            return new ImportItemResult($article->externalId, $article->title, ImportItemOutcome::Created, $tags);
        }

        if ($result->getError()->code === ArticleErrors::ALREADY_IMPORTED) {
            return $skip(ImportItemOutcome::AlreadyImported);
        }

        return $this->failed($source, $article, $result->getError()->detail);
    }

    private function failed(ContentSourceDTO $source, ExternalArticle $article, string $detail): ImportItemResult
    {
        Log::warning('Content import could not create an article', [
            'source' => $source->key,
            'external_id' => $article->externalId,
            'error' => $detail,
        ]);

        return new ImportItemResult($article->externalId, $article->title, ImportItemOutcome::Failed, detail: $detail);
    }
}
