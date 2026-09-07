<?php

declare(strict_types=1);

namespace App\Application\Articles\DTOs;

use App\Domain\Articles\Models\Article;
use App\Domain\Articles\Models\ArticleStats;

/**
 * One enriched row of an Article list page.
 *
 * Replaces the Domain DTO of the same name, which imported the Eloquent
 * LastOperationState model straight into the domain layer.
 */
final readonly class ArticleListItemDTO
{
    /**
     * @param array<int, array{id: int|string, content: string, created_at?: mixed, updated_at?: mixed}|object> $hashtags
     */
    public function __construct(
        public Article $article,
        public ?ArticleStats $stats,
        public array $hashtags,
        public ?ArticleProcessingStateDTO $processingState,
    ) {
    }
}
