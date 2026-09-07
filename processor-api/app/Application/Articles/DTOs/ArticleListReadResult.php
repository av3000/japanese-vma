<?php

declare(strict_types=1);

namespace App\Application\Articles\DTOs;

use App\Domain\Articles\Models\Article;

/**
 * What a reader returns: eligible Articles for one page, plus page metadata.
 *
 * Enrichment is deliberately absent. Readers resolve eligibility, ordering and
 * pagination; SearchArticlesAction adds stats, hashtags and processing state so
 * a future search-engine reader does not have to reimplement that batching.
 */
final readonly class ArticleListReadResult
{
    /**
     * @param array<int, Article> $articles
     */
    public function __construct(
        public array $articles,
        public ArticlePaginationDTO $pagination,
    ) {
    }
}
