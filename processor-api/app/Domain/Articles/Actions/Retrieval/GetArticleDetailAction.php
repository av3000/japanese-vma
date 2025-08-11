<?php
namespace App\Domain\Articles\Actions\Retrieval;

use App\Domain\Articles\Interfaces\Actions\GetArticleDetailActionInterface;
use App\Domain\Engagement\Actions\IncrementViewAction;
use App\Domain\Articles\Actions\Retrieval\LoadArticleDetailStatsAction;
use App\Domain\Articles\Actions\Processing\ProcessWordMeaningsAction;
use App\Domain\Engagement\Actions\LoadArticleCommentsAction;
use App\Domain\Articles\Models\Article;

class GetArticleDetailAction implements GetArticleDetailActionInterface
{
    public function __construct(
        private IncrementViewAction $incrementView,
        private LoadArticleDetailStatsAction $loadStats,
        private ProcessWordMeaningsAction $processWords,
        private LoadArticleCommentsAction $loadComments
    ) {}

    /**
     * Retrieve an article with all detail-level data loaded.
     * Orchestrates several focused actions to build a complete article view.
     */
    public function execute(int $id): ?Article
    {
        // Load the article with essential relationships upfront
        // Prevents N+1 queries for the basic data we know we'll need
        $article = Article::with(['user', 'kanjis', 'words'])->find($id);

        if (!$article) {
            return null;
        }

        $this->incrementView->execute($article);

        $this->loadStats->execute($article);

        // Placeholder that will be replaced once ACtion is implemented optimization is complete
        $this->processWords->execute($article);

        $this->loadComments->execute($article);

        return $article;
    }
}
