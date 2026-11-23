<?php

declare(strict_types=1);

namespace App\Domain\Community\Posts\DTOs;

final readonly class PostListResultDTO
{
    /**
     * @param array<int, PostListItemDTO> $items
     * @param array{page: int, per_page: int, total: int, last_page: int, has_more: bool} $pagination
     */
    public function __construct(
        public array $items,
        public array $pagination,
    ) {
    }
}
