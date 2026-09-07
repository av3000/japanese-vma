<?php

declare(strict_types=1);

namespace App\Application\Articles\DTOs;

/**
 * A fully projected Article list page, ready for an API resource.
 */
final readonly class ArticleListPageDTO
{
    /**
     * @param array<int, ArticleListItemDTO> $items
     * @param array<int, ArticleFacetDTO> $facets empty when facets were not requested
     * @param array{q: ?string, filters: array<string, mixed>, sort: string} $query
     */
    public function __construct(
        public array $items,
        public ArticlePaginationDTO $pagination,
        public ArticleListProjection $projection,
        public array $facets = [],
        public array $query = [],
    ) {
    }
}
