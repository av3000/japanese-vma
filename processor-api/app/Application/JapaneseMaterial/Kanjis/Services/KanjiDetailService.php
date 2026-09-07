<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Kanjis\Services;

use App\Application\Articles\Actions\Retrieval\SearchArticlesAction;
use App\Application\Articles\DTOs\ArticleListProjection;
use App\Application\Articles\DTOs\ArticleListQuery;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Catalogues\Services\ViewerCatalogueStateService;
use App\Application\JapaneseMaterial\Sentences\Services\SentenceServiceInterface;
use App\Application\JapaneseMaterial\Words\Services\WordServiceInterface;
use App\Domain\Articles\ValueObjects\ArticleListSort;
use App\Domain\JapaneseMaterial\Kanjis\DTOs\KanjiDetailIncludes;
use App\Domain\JapaneseMaterial\Kanjis\DTOs\KanjiDetailResultDTO;
use App\Domain\JapaneseMaterial\Sentences\Queries\SentenceQueryCriteria;
use App\Domain\JapaneseMaterial\Words\Queries\WordQueryCriteria;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Shared\Results\Result;

final readonly class KanjiDetailService implements KanjiDetailServiceInterface
{
    private const RELATED_PER_PAGE = 5;

    public function __construct(
        private KanjiServiceInterface $kanjiService,
        private WordServiceInterface $wordService,
        private SentenceServiceInterface $sentenceService,
        private SearchArticlesAction $searchArticles,
        private ViewerCatalogueStateService $viewerCatalogueStateService,
    ) {
    }

    public function findByIdentifier(
        string $identifier,
        KanjiDetailIncludes $includes,
        ?AuthenticatedUser $authenticatedUser = null,
    ): Result {
        $kanjiResult = $this->kanjiService->findByIdentifier($identifier);

        if ($kanjiResult->isFailure()) {
            return $kanjiResult;
        }

        $kanji = $kanjiResult->getData();
        $kanjiId = $kanji->getIdValue();

        $words = $includes->words
            ? $this->wordService->find(WordQueryCriteria::forListing(
                perPage: self::RELATED_PER_PAGE,
                kanjiId: $kanjiId,
            ))->getData()
            : null;

        $sentences = $includes->sentences
            ? $this->sentenceService->find(SentenceQueryCriteria::forListing(
                perPage: self::RELATED_PER_PAGE,
                kanjiId: $kanjiId,
            ))->getData()
            : null;

        $articles = $includes->articles
            ? $this->searchArticles->execute(
                new ArticleListQuery(
                    sort: ArticleListSort::fromSigned('-created_at'),
                    pagination: new Pagination(1, self::RELATED_PER_PAGE),
                    kanjiIds: [$kanjiId],
                ),
                // The panel shows engagement counts and tags, but not nested kanji or
                // word lists, so it does not pay to load them.
                new ArticleListProjection(
                    includeStats: true,
                    includeHashtags: true,
                    includeKanjis: false,
                    includeWords: false,
                ),
                $authenticatedUser,
            )
            : null;

        $viewerState = null;

        if ($includes->viewerCatalogueState && $authenticatedUser !== null) {
            $viewerState = $this->viewerCatalogueStateService->forItems(
                ownerUuid: $authenticatedUser->uuid,
                itemIds: [$kanjiId],
                savedType: SavedListType::KANJIS,
                knownType: SavedListType::KNOWNKANJIS,
            )[$kanjiId] ?? null;
        }

        return Result::success(new KanjiDetailResultDTO(
            kanji: $kanji,
            words: $words,
            sentences: $sentences,
            articles: $articles,
            viewerCatalogueState: $viewerState,
        ));
    }
}
