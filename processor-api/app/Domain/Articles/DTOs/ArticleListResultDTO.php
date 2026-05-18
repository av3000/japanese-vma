<?php

declare(strict_types=1);

namespace App\Domain\Articles\DTOs;

readonly class ArticleListResultDTO
{
    /**
     * @param array<int, ArticleListItemDTO> $items
     * @param array{page: int, per_page: int, total: int, last_page: int, has_more: bool} $pagination
     */
    public function __construct(
        public array $items,
        public array $pagination,
        public bool $include_hashtags,
        public bool $include_stats,
    ) {
    }
}
