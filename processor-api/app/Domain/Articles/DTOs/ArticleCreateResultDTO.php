<?php

declare(strict_types=1);

namespace App\Domain\Articles\DTOs;

use App\Domain\Articles\Models\Article;

/**
 * What a successful create hands back: the article plus the `pending` processing row that was
 * opened in the same transaction, so the response can show a status before any worker runs.
 */
final readonly class ArticleCreateResultDTO
{
    public function __construct(
        public Article $article,
        public ArticleProcessingStateDTO $processingState,
    ) {
    }
}
