<?php

declare(strict_types=1);

namespace App\Domain\Articles\DTOs;

use App\Domain\Articles\Models\Article;

readonly class ArticleUpdateResultDTO
{
    /**
     * TODO: Consider a follow-up GitHub issue to move use-case result DTOs
     * from Domain DTO namespaces into Application DTO namespaces.
     */
    public function __construct(
        public Article $article,
        public array $hashtags,
    ) {
    }
}
