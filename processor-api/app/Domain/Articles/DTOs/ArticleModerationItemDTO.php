<?php

declare(strict_types=1);

namespace App\Domain\Articles\DTOs;

use App\Domain\Articles\Models\Article;

final readonly class ArticleModerationItemDTO
{
    /**
     * @param array<int, array{id: int|string, content: string, created_at?: mixed, updated_at?: mixed}|object> $hashtags
     */
    public function __construct(
        public Article $article,
        public array $hashtags,
    ) {
    }
}
