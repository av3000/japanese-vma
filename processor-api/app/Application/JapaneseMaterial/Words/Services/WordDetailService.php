<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Words\Services;

use App\Application\Articles\Actions\Retrieval\SearchArticlesAction;
use App\Application\Articles\DTOs\ArticleListProjection;
use App\Application\Articles\DTOs\ArticleListQuery;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\JapaneseMaterial\Words\Interfaces\Repositories\WordRepositoryInterface;
use App\Domain\Articles\ValueObjects\ArticleListSort;
use App\Domain\JapaneseMaterial\Words\DTOs\WordDetailIncludes;
use App\Domain\JapaneseMaterial\Words\DTOs\WordDetailResultDTO;
use App\Domain\JapaneseMaterial\Words\Models\Word;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Shared\Results\Result;

final readonly class WordDetailService implements WordDetailServiceInterface
{
    private const RELATED_LIMIT = 5;

    public function __construct(
        private WordServiceInterface $wordService,
        private WordRepositoryInterface $wordRepository,
        private SearchArticlesAction $searchArticles,
    ) {
    }

    public function findByIdentifier(
        string $identifier,
        WordDetailIncludes $includes,
        ?AuthenticatedUser $authenticatedUser = null,
    ): Result {
        $wordResult = $this->wordService->findByIdentifier($identifier);

        if ($wordResult->isFailure()) {
            return $wordResult;
        }

        /** @var Word $word */
        $word = $wordResult->getData();
        $wordId = $word->getIdValue();

        $kanjis = $includes->kanjis
            ? $this->wordRepository->findRelatedKanjis($wordId, self::RELATED_LIMIT)
            : null;

        $articles = $includes->articles
            ? $this->searchArticles->execute(
                new ArticleListQuery(
                    // Ordered by id ascending, as this panel always has been. Not a
                    // public sort value: `id` is the deterministic tie-breaker appended
                    // to every sort, exposed here only through the internal constructor.
                    sort: ArticleListSort::fromLegacy('id', 'asc'),
                    pagination: new Pagination(1, self::RELATED_LIMIT),
                    wordIds: [$wordId],
                ),
                new ArticleListProjection(
                    includeStats: true,
                    includeHashtags: true,
                    includeKanjis: false,
                    includeWords: false,
                ),
                $authenticatedUser,
            )->items
            : null;

        return Result::success(new WordDetailResultDTO(
            word: $word,
            kanjis: $kanjis,
            articles: $articles,
        ));
    }
}
