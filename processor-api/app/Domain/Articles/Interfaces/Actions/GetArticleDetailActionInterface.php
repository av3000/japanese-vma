<?php
namespace App\Domain\Articles\Interfaces\Actions;

use App\Domain\Articles\Models\Article;

interface GetArticleDetailActionInterface
{
    /**
     * Retrieve an article with all detail-level data loaded
     *
     * This includes loading relationships, tracking the view,
     * loading statistics, processing word meanings, and loading comments
     *
     * @param int $id The ID of the article to retrieve
     * @return Article|null The fully loaded article or null if not found
     */
    public function execute(int $id): ?Article;
}
