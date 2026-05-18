<?php

declare(strict_types=1);

namespace App\Domain\Articles\DTOs;

use App\Domain\Articles\Models\Article;
use App\Domain\Engagement\DTOs\EngagementSummary;
use App\Infrastructure\Persistence\Models\LastOperationState;

readonly class ArticleDetailResultDTO
{
    /**
     * @param array<int, mixed> $kanjis
     * @param array<int, mixed> $words
     * @param array<int, array{id: int|string, content: string, created_at?: mixed, updated_at?: mixed}|object> $hashtags
     */
    public function __construct(
        public Article $article,
        public EngagementSummary $engagement,
        public array $kanjis,
        public array $words,
        public array $hashtags,
        public ?LastOperationState $lastOperation,
    ) {
    }
}
