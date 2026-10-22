<?php

declare(strict_types=1);

namespace App\Application\ContentImport\Services;

use App\Application\Articles\Services\ArticleServiceInterface;
use App\Application\ContentImport\Interfaces\ArticleTaggerInterface;
use App\Application\ContentImport\Interfaces\ContentSourceAdapterInterface;
use App\Application\ContentImport\Interfaces\ContentSourceAdapterRegistryInterface;
use App\Application\ContentImport\Interfaces\Providers\ImportSettingsProviderInterface;
use App\Application\ContentImport\Interfaces\Providers\SystemAuthorProviderInterface;
use App\Application\ContentImport\Interfaces\Repositories\ContentSourceRepositoryInterface;
use App\Application\ContentImport\Runs\ImportRunItems;
use App\Application\ContentImport\Runs\ImportRunRecorder;
use App\Domain\Articles\DTOs\ArticleCreateDTO;
use App\Domain\Articles\Errors\ArticleErrors;
use App\Domain\Articles\ValueObjects\ArticleAuthor;
use App\Domain\Articles\ValueObjects\ArticleProvenance;
use App\Domain\ContentImport\DTOs\ContentSourceDTO;
use App\Domain\ContentImport\DTOs\ExternalArticle;
use App\Domain\ContentImport\DTOs\ImportItemResult;
use App\Domain\ContentImport\Enums\ImportItemOutcome;
use App\Domain\ContentImport\Errors\ContentImportErrors;
use App\Domain\ContentImport\ValueObjects\ImportRunSettings;
use App\Shared\Results\Result;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * One Import Run: list what the source has that is not imported yet, filter it, tag it, and
 * create each survivor as a public Imported Article through the ordinary article creation path.
 * The bookkeeping around that work (one run per source at a time, the run row, failures) is
 * ImportRunRecorder's.
 *
 * Volume is bounded twice: by a cap on articles created per run, and by a ceiling on how many
 * listed articles one run may look at, so a source full of filtered items cannot page forever.
 * Articles already imported are skipped rather than ending the listing, so one that failed is
 * tried again by the next run while the source still lists it.
 */
class ContentImportService implements ContentImportServiceInterface
{
    public function __construct(
        private readonly ContentSourceRepositoryInterface $sources,
        private readonly ContentSourceAdapterRegistryInterface $adapters,
        private readonly ImportSettingsProviderInterface $settings,
        private readonly ImportRunRecorder $recorder,
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

        return $this->recorder->record(
            $source,
            $dryRun,
            $settings->stalledAfterRuns,
            fn (ImportRunItems $items) => $this->importNew($source, $adapter, $author, $settings, $dryRun, $items),
        );
    }

    /**
     * Walks the source's listing until it runs out or a limit is reached. The limit is checked
     * before asking for the next article, so the lazy listing never fetches one it will not use.
     */
    private function importNew(
        ContentSourceDTO $source,
        ContentSourceAdapterInterface $adapter,
        ArticleAuthor $author,
        ImportRunSettings $settings,
        bool $dryRun,
        ImportRunItems $items,
    ): void {
        if ($settings->isLimitReached($items->created(), $items->listed())) {
            return;
        }

        $listing = $adapter->listRecent(
            fn (string $externalId): bool => $this->articles->hasImportedArticle($source->id, $externalId),
        );

        foreach ($listing as $article) {
            $items->add($this->importOne($source, $article, $author, $settings, $dryRun));

            if ($settings->isLimitReached($items->created(), $items->listed())) {
                return;
            }
        }
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

        // Another run of the same source created this article between our "already imported?"
        // check and the insert. The unique index caught it; for this run it is simply a skip.
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
