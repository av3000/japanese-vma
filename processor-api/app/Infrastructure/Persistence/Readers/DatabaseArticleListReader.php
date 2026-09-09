<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Readers;

use App\Application\Articles\Interfaces\Readers\ArticleListReaderInterface;
use App\Domain\Articles\DTOs\ArticleListIncludes;
use App\Domain\Articles\DTOs\ArticlePageDTO;
use App\Domain\Articles\DTOs\ArticlePaginationDTO;
use App\Domain\Articles\Models\Article as DomainArticle;
use App\Domain\Articles\Queries\ArticleQueryCriteria;
use App\Domain\Articles\ValueObjects\ArticleVisibilityScope;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Readers\ArticleFacets\HashtagFacetCounter;
use App\Infrastructure\Persistence\Readers\ArticleFacets\JlptLevelFacetCounter;
use App\Infrastructure\Persistence\Repositories\ArticleMapper;
use Illuminate\Database\Eloquent\Builder;

/**
 * The Eloquent implementation of the Article read port.
 *
 * Everything framework-shaped about the Article list lives here: the query builder,
 * ordering and pagination. Callers above this class see only application and domain
 * types.
 *
 * Eligibility itself lives in ArticleListFilterBuilder, shared with the facet counters, so
 * items and counts cannot drift apart.
 */
final readonly class DatabaseArticleListReader implements ArticleListReaderInterface
{
    public function __construct(
        private ArticleMapper $articleMapper,
        private ArticleListFilterBuilder $filterBuilder,
        private JlptLevelFacetCounter $jlptFacets,
        private HashtagFacetCounter $hashtagFacets,
    ) {
    }

    public function search(
        ArticleQueryCriteria $criteria,
        ArticleVisibilityScope $scope,
        ArticleListIncludes $includes,
    ): ArticlePageDTO {
        $builder = $this->filterBuilder->newQuery($criteria, $scope)->with(['user']);

        $this->applyEagerLoads($builder, $includes);
        $this->applySorting($builder, $criteria);

        $paginator = $builder->paginate(
            $criteria->pagination->per_page,
            ['*'],
            'page',
            $criteria->pagination->page,
        );

        $articles = $paginator
            ->getCollection()
            ->map(fn (PersistenceArticle $article): DomainArticle => $this->articleMapper->mapToDomain($article, $includes))
            ->values()
            ->all();

        return new ArticlePageDTO(
            articles: $articles,
            pagination: new ArticlePaginationDTO(
                page: $paginator->currentPage(),
                perPage: $paginator->perPage(),
                total: $paginator->total(),
                lastPage: $paginator->lastPage(),
                hasMore: $paginator->hasMorePages(),
            ),
        );
    }

    /**
     * Facets are counted from the same scope the items came from. A bounded number of
     * queries: one per dimension, independent of page size and of how many values
     * each dimension returns.
     */
    public function facets(ArticleQueryCriteria $criteria, ArticleVisibilityScope $scope): array
    {
        return [
            $this->jlptFacets->count($criteria, $scope),
            $this->hashtagFacets->count($criteria, $scope),
        ];
    }

    /**
     * Includes may only change what is loaded, never which rows are eligible.
     */
    private function applyEagerLoads(Builder $builder, ArticleListIncludes $includes): void
    {
        if ($includes->includeKanjis) {
            $builder->with('kanjis');
        }

        // Words are deliberately not eager-loaded. ArticleResource exposes no `words`
        // field on list items, so loading them costs a query per page and renders
        // nothing. includeWords stays inert for lists until a product decision adds
        // that field; AFM-01 raised the question and it is still open.
    }

    /**
     * The id tie-breaker is what makes paging stable: without it, Articles sharing a
     * created_at can reorder between requests and a user paging through the list sees
     * duplicates and gaps.
     */
    private function applySorting(Builder $builder, ArticleQueryCriteria $criteria): void
    {
        $direction = $criteria->sort->direction->value;

        $builder->orderBy('articles.'.$criteria->sort->field->value, $direction)
            ->orderBy('articles.id', $direction);
    }
}
