<?php

declare(strict_types=1);

namespace App\Domain\Articles\DTOs;

use App\Domain\Articles\Models\Article;
use App\Domain\Articles\Models\ArticleStats;
use App\Infrastructure\Persistence\Models\LastOperationState;

readonly class ArticleListItemDTO
{
    /**
     * @param array<int, array{id: int|string, content: string, created_at?: mixed, updated_at?: mixed}|object> $hashtags
     */
    public function __construct(
        public Article $article,
        public ?ArticleStats $stats,
        public array $hashtags,
        public ?LastOperationState $lastOperation,
    ) {
    }
}
