<?php
namespace App\Domain\Articles\Actions\Retrieval;

use App\Domain\Engagement\Actions\IncrementViewAction;
use App\Domain\Articles\Actions\Retrieval\LoadArticleDetailStatsAction;
use App\Domain\Articles\Actions\Processing\ProcessWordMeaningsAction;
use App\Domain\Engagement\Actions\LoadArticleCommentsAction;

use App\Domain\Articles\Models\Article;

class GetArticleDetailAction
{
    public function __construct(
        private IncrementViewAction $incrementView,
        private LoadArticleDetailStatsAction $loadStats,
        private ProcessWordMeaningsAction $processWords,
        private LoadArticleCommentsAction $loadComments
    ) {}

    /**
     * Retrieve an article with all detail-level data loaded.
     * This orchestrates several focused actions to build a complete article view
     * while maintaining separation of concerns and performance optimization.
     */
    public function execute(int $id): ?Article
    {
        // Load the article with essential relationships upfront
        // This prevents N+1 queries for the basic data we know we'll need
        $article = Article::with(['user', 'kanjis', 'words'])->find($id);

        if (!$article) {
            return null;
        }

        // Track this view (works for authenticated and anonymous users)
        $this->incrementView->execute($article);

        // Load engagement statistics (likes, downloads, views, comments counts)
        // This reuses your efficient batch loading logic adapted for single articles
        $this->loadStats->execute($article);

        // Process word meanings (currently disabled due to performance issues)
        // This is a placeholder that will be replaced once optimization is complete
        $this->processWords->execute($article);

        // Load comments with associated user data and like counts
        // Uses batch loading to avoid N+1 query problems
        $this->loadComments->execute($article);

        return $article;
    }
}
