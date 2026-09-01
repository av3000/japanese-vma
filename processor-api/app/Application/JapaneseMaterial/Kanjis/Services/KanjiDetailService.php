<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Kanjis\Services;

use App\Application\Articles\Services\ArticleServiceInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Catalogues\Services\ViewerCatalogueStateService;
use App\Application\JapaneseMaterial\Sentences\Services\SentenceServiceInterface;
use App\Application\JapaneseMaterial\Words\Services\WordServiceInterface;
use App\Domain\Articles\DTOs\ArticleListDTO;
use App\Domain\JapaneseMaterial\Kanjis\DTOs\KanjiDetailIncludes;
use App\Domain\JapaneseMaterial\Kanjis\DTOs\KanjiDetailResultDTO;
use App\Domain\JapaneseMaterial\Sentences\Queries\SentenceQueryCriteria;
use App\Domain\JapaneseMaterial\Words\Queries\WordQueryCriteria;
use App\Domain\Shared\Enums\SavedListType;
use App\Shared\Results\Result;

final readonly class KanjiDetailService implements KanjiDetailServiceInterface
{
    private const RELATED_PER_PAGE = 5;

    public function __construct(
        private KanjiServiceInterface $kanjiService,
        private WordServiceInterface $wordService,
        private SentenceServiceInterface $sentenceService,
        private ArticleServiceInterface $articleService,
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
            ? $this->articleService->getArticlesList(new ArticleListDTO(
                category: null,
                search: null,
                author_uid: null,
                sort_by: 'created_at',
                sort_dir: 'desc',
                per_page: self::RELATED_PER_PAGE,
                page: 1,
                include_stats_counts: true,
                include_hashtags: true,
                include_kanjis: false,
                include_words: false,
                kanji_id: $kanjiId,
            ), $authenticatedUser)
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
