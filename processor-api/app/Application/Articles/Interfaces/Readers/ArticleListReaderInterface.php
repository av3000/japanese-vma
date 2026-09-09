<?php

declare(strict_types=1);

namespace App\Application\Articles\Interfaces\Readers;

use App\Domain\Articles\DTOs\ArticleFacetDTO;
use App\Domain\Articles\DTOs\ArticleListIncludes;
use App\Domain\Articles\DTOs\ArticlePageDTO;
use App\Domain\Articles\Models\Article;
use App\Domain\Articles\Queries\ArticleQueryCriteria;
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
        ArticleQueryCriteria $criteria,
        ArticleVisibilityScope $scope,
        ArticleListIncludes $includes,
    ): ArticlePageDTO;

    /**
     * The same rows search() would return for the criteria's page, without the
     * total. Related-Article panels show a fixed number of rows and never a count,
     * so they must not pay for one.
     *
     * @return array<int, Article>
     */
    public function listWithoutTotal(
        ArticleQueryCriteria $criteria,
        ArticleVisibilityScope $scope,
        ArticleListIncludes $includes,
    ): array;

    /**
     * Disjunctive counts per dimension: each facet excludes its own active selection
     * while retaining the mandatory scope and every other filter.
     *
     * @return array<int, ArticleFacetDTO>
     */
    public function facets(ArticleQueryCriteria $criteria, ArticleVisibilityScope $scope): array;
}
