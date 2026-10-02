<?php

declare(strict_types=1);

namespace App\Application\ContentImport\Services;

use App\Application\Articles\Services\ArticleServiceInterface;
use App\Application\ContentImport\Interfaces\ArticleTaggerInterface;
use App\Application\ContentImport\Interfaces\ContentSourceAdapterRegistryInterface;
use App\Application\ContentImport\Interfaces\Providers\SystemAuthorProviderInterface;
use App\Application\ContentImport\Interfaces\Readers\ImportedArticleReaderInterface;
use App\Application\ContentImport\Interfaces\Repositories\ContentSourceRepositoryInterface;
use App\Application\ContentImport\Interfaces\Repositories\ImportRunRepositoryInterface;
use App\Domain\Articles\DTOs\ArticleCreateDTO;
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
use App\Shared\Results\Result;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * One Import Run: list what the source has that is new, filter it, tag it, and create each
 * survivor as a public Imported Article through the ordinary article creation path.
 *
 * Volume is bounded twice: by the daily cap on created articles, and by a ceiling on how many
 * listed articles one run may look at, so a source full of filtered items cannot page forever.
 */
class ContentImportService implements ContentImportServiceInterface
{
    public function __construct(
        private readonly ContentSourceRepositoryInterface $sources,
        private readonly ContentSourceAdapterRegistryInterface $adapters,
        private readonly ImportedArticleReaderInterface $importedArticles,
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

        $settings = $this->settings($sourceKey);
        $runId = $dryRun ? null : $this->runs->start($source->id);
        $items = [];
        $created = 0;
        $status = ImportRunStatus::Succeeded;
        $error = null;

        try {
            $listing = $adapter->listRecent(
                fn (string $externalId): bool => $this->importedArticles->exists($source->id, $externalId),
            );

            // Checked after each item, so the lazy listing is never asked for one it will not use.
            $limitReached = function () use (&$created, &$items, $settings): bool {
                return $created >= $settings['daily_cap'] || count($items) >= $settings['max_listed'];
            };

            if (! $limitReached()) {
                foreach ($listing as $article) {
                    $item = $this->importOne($source, $article, $author, $settings, $dryRun);
                    $items[] = $item;

                    if ($item->outcome === ImportItemOutcome::Created || $item->outcome === ImportItemOutcome::WouldCreate) {
                        $created++;
                    }

                    if ($limitReached()) {
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
            $this->hasStalled($source, $settings['stalled_after_runs']),
        ));
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

    /**
     * @param array{daily_cap: int, max_listed: int, min_lead_length: int, excluded_genres: list<string>, stalled_after_runs: int} $settings
     */
    private function importOne(
        ContentSourceDTO $source,
        ExternalArticle $article,
        ArticleAuthor $author,
        array $settings,
        bool $dryRun,
    ): ImportItemResult {
        $skip = fn (ImportItemOutcome $outcome, ?string $detail = null): ImportItemResult => new ImportItemResult(
            $article->externalId,
            $article->title,
            $outcome,
            detail: $detail,
        );

        // Excluded only when every genre is excluded: an AI story filed under both 気象・災害 and
        // 科学・文化 is still worth reading, a bare weather bulletin is not.
        if ($article->genres !== [] && array_diff($article->genres, $settings['excluded_genres']) === []) {
            return $skip(ImportItemOutcome::FilteredGenre, implode(', ', $article->genres));
        }

        if (mb_strlen(trim($article->lead)) < $settings['min_lead_length']) {
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

        if ($result->getError()->code === 'Articles.AlreadyImported') {
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

    /**
     * @return array{daily_cap: int, max_listed: int, min_lead_length: int, excluded_genres: list<string>, stalled_after_runs: int}
     */
    private function settings(string $sourceKey): array
    {
        $defaults = (array) config('content_import.defaults', []);
        $source = (array) config("content_import.sources.{$sourceKey}", []);
        $merged = array_merge($defaults, $source);

        return [
            'daily_cap' => (int) ($merged['daily_cap'] ?? 10),
            'max_listed' => (int) ($merged['max_listed'] ?? 100),
            'min_lead_length' => (int) ($merged['min_lead_length'] ?? 60),
            'excluded_genres' => array_values((array) ($merged['excluded_genres'] ?? [])),
            'stalled_after_runs' => (int) ($merged['stalled_after_runs'] ?? 3),
        ];
    }
}
