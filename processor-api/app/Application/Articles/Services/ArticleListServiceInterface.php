<?php

declare(strict_types=1);

namespace App\Application\Articles\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Articles\DTOs\ArticleListIncludes;
use App\Domain\Articles\Queries\ArticleQueryCriteria;
use App\Shared\Results\Result;

/**
 * The Article list use case, and the only entry point other modules use to read
 * Articles in bulk.
 *
 * Eligibility is `mandatory visibility scope AND user intent`. The scope comes from
 * the actor alone, so nothing in $criteria can widen what the caller may see.
 */
interface ArticleListServiceInterface
{
    /**
     * A page with total, facets and the applied-criteria echo.
     *
     * @return Result Success data: ArticleListResultDTO
     */
    public function list(
        ArticleQueryCriteria $criteria,
        ArticleListIncludes $includes,
        ?AuthenticatedUser $actor = null,
    ): Result;

    /**
     * The enriched rows for one page and nothing else: no total, no facets. For
     * related-Article panels that render a fixed number of rows.
     *
     * @return Result Success data: array<int, ArticleListItemDTO>
     */
    public function listItems(
        ArticleQueryCriteria $criteria,
        ArticleListIncludes $includes,
        ?AuthenticatedUser $actor = null,
    ): Result;
}
