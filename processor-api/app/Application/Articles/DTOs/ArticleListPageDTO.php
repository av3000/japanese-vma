<?php

declare(strict_types=1);

namespace App\Application\Articles\DTOs;

/**
 * A fully projected Article list page, ready for an API resource.
 *
 * AFM-05 adds `facets` and a canonical `query` echo alongside these fields.
 */
final readonly class ArticleListPageDTO
{
    /**
     * @param array<int, ArticleListItemDTO> $items
     */
    public function __construct(
        public array $items,
        public ArticlePaginationDTO $pagination,
        public ArticleListProjection $projection,
    ) {
    }
}
