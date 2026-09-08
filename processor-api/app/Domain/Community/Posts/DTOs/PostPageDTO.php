<?php

declare(strict_types=1);

namespace App\Domain\Community\Posts\DTOs;

use App\Domain\Community\Posts\Models\Post;

/**
 * Repository output: domain Posts plus scalar pagination metadata.
 *
 * A Laravel paginator never crosses the repository port; enrichment happens in
 * PostReadService, which turns this into a PostListResultDTO.
 */
final readonly class PostPageDTO
{
    /**
     * @param array<int, Post> $items
     * @param array{page: int, per_page: int, total: int, last_page: int, has_more: bool} $pagination
     */
    public function __construct(
        public array $items,
        public array $pagination,
    ) {
    }
}
