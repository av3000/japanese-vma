<?php

declare(strict_types=1);

namespace App\Domain\Articles\DTOs;

final readonly class ArticleModerationListResultDTO
{
    /**
     * @param array<int, ArticleModerationItemDTO> $items
     * @param array{page: int, per_page: int, total: int, last_page: int, has_more: bool} $pagination
     */
    public function __construct(
        public array $items,
        public array $pagination,
    ) {
    }
}
