<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Readers;

use App\Application\Articles\DTOs\ArticleListProjection;
use App\Application\Articles\DTOs\ArticleListQuery;
use App\Application\Articles\DTOs\ArticleListReadResult;
use App\Application\Articles\DTOs\ArticlePaginationDTO;
use App\Application\Articles\Interfaces\Readers\ArticleListReaderInterface;
use App\Domain\Articles\DTOs\ArticleIncludeOptionsDTO;
use App\Domain\Articles\Models\Article as DomainArticle;
use App\Domain\Articles\ValueObjects\ArticleVisibilityScope;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Readers\ArticleFacets\HashtagFacetCounter;
use App\Infrastructure\Persistence\Readers\ArticleFacets\JlptLevelFacetCounter;
use App\Infrastructure\Persistence\Repositories\ArticleMapper;
use Illuminate\Database\Eloquent\Builder;

/**
 * The Eloquent implementation of the Article read port.
 *
 * Everything framework-shaped about Article discovery lives here: the query builder,
 * ordering and pagination. Callers above this class see only application and domain
 * types.
 *
 * Eligibility itself lives in ArticleQueryScope, shared with the facet counters, so
 * items and counts cannot drift apart.
 */
final readonly class DatabaseArticleListReader implements ArticleListReaderInterface
{
    public function __construct(
        private ArticleMapper $articleMapper,
        private ArticleQueryScope $queryScope,
        private JlptLevelFacetCounter $jlptFacets,
        private HashtagFacetCounter $hashtagFacets,
    ) {
    }

    public function search(
        ArticleListQuery $query,
        ArticleVisibilityScope $scope,
        ArticleListProjection $projection,
    ): ArticleListReadResult {
        $builder = $this->queryScope->newQuery($query, $scope)->with(['user']);

        $this->applyEagerLoads($builder, $projection);
        $this->applySorting($builder, $query);

        $paginator = $builder->paginate(
            $query->pagination->per_page,
            ['*'],
            'page',
            $query->pagination->page,
        );

        $includeOptions = new ArticleIncludeOptionsDTO(
            include_kanjis: $projection->includeKanjis,
            include_words: $projection->includeWords,
        );

        $articles = $paginator
            ->getCollection()
            ->map(fn (PersistenceArticle $article): DomainArticle => $this->articleMapper->mapToDomain($article, $includeOptions))
            ->values()
            ->all();

        return new ArticleListReadResult(
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
    public function facets(ArticleListQuery $query, ArticleVisibilityScope $scope): array
    {
        return [
            $this->jlptFacets->count($query, $scope),
            $this->hashtagFacets->count($query, $scope),
        ];
    }

    /**
     * Projection may only change what is loaded, never which rows are eligible.
     */
    private function applyEagerLoads(Builder $builder, ArticleListProjection $projection): void
    {
        if ($projection->includeKanjis) {
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
    private function applySorting(Builder $builder, ArticleListQuery $query): void
    {
        $direction = $query->sort->direction->value;

        $builder->orderBy('articles.'.$query->sort->field->value, $direction)
            ->orderBy('articles.id', $direction);
    }
}
