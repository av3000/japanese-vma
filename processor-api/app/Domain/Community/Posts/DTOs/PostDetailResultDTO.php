<?php

declare(strict_types=1);

namespace App\Domain\Community\Posts\DTOs;

use App\Domain\Community\Posts\Models\Post;
use App\Domain\Community\Posts\Models\PostStats;

final readonly class PostDetailResultDTO
{
    /**
     * @param array<int, object> $hashtags
     */
    public function __construct(
        public Post $post,
        public PostStats $stats,
        public array $hashtags,
    ) {
    }
}
