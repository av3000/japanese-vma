<?php

declare(strict_types=1);

namespace App\Domain\Articles\DTOs;

use App\Domain\Articles\Queries\ArticleQueryCriteria;

/**
 * An enriched Article list page, ready for an API resource.
 *
 * ArticlePageDTO is the raw reader output; this is what the list use case returns
 * after batching stats, hashtags, processing state and facets onto it.
 */
final readonly class ArticleListResultDTO
{
    /**
     * @param array<int, ArticleListItemDTO> $items
     * @param array<int, ArticleFacetDTO> $facets empty when facets were not requested
     * @param ArticleQueryCriteria $criteria the user intent that produced this page, echoed back as `applied`
     */
    public function __construct(
        public array $items,
        public ArticlePaginationDTO $pagination,
        public ArticleListIncludes $includes,
        public ArticleQueryCriteria $criteria,
        public array $facets = [],
    ) {
    }
}
