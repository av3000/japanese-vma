<?php

declare(strict_types=1);

namespace App\Application\Articles\Interfaces\Readers;

use App\Application\Articles\DTOs\ArticleFacetDTO;
use App\Application\Articles\DTOs\ArticleListProjection;
use App\Application\Articles\DTOs\ArticleListQuery;
use App\Application\Articles\DTOs\ArticleListReadResult;
use App\Domain\Articles\ValueObjects\ArticleVisibilityScope;

/**
 * The read port for Article discovery.
 *
 * DatabaseArticleListReader is the only implementation today. A future
 * SearchEngineArticleListReader would satisfy the same contract, which is why
 * nothing here may mention Eloquent, a query builder, or a Laravel paginator.
 *
 * Implementations must apply the scope to every result set they produce - rows,
 * totals and facet counts - so eligibility cannot drift between them.
 */
interface ArticleListReaderInterface
{
    public function search(
        ArticleListQuery $query,
        ArticleVisibilityScope $scope,
        ArticleListProjection $projection,
    ): ArticleListReadResult;

    /**
     * Disjunctive counts per dimension: each facet excludes its own active selection
     * while retaining the mandatory scope and every other filter.
     *
     * @return array<int, ArticleFacetDTO>
     */
    public function facets(ArticleListQuery $query, ArticleVisibilityScope $scope): array;
}
