<?php
namespace App\Application\Articles\Actions\Retrieval;

use App\Application\Articles\Actions\Retrieval\{LoadArticleListStatsAction, LoadArticleListHashtagsAction};

use App\Domain\Articles\Http\Models\Article;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class LoadArticleDetailStatsAction
{
    public function __construct(
        private LoadArticleListStatsAction $loadListStats,
        private LoadArticleListHashtagsAction $loadListHashtags
    ) {}

    /**
     * Load detailed stats for a single article by adapting our batch loading actions.
     * This demonstrates how we can reuse existing efficient batch loading logic
     * even for single-item scenarios by creating a temporary collection.
     */
    public function execute(Article $article): void
    {
        // Create a temporary paginator with just our single article
        // This allows us to reuse the efficient batch loading logic
        $singleArticles = new LengthAwarePaginator(
            items: collect([$article]),
            total: 1,
            perPage: 1,
            currentPage: 1
        );

        // Use existing batch loading actions
        $this->loadListStats->execute($singleArticles);
        $this->loadListHashtags->execute($singleArticles);

        // Calculate kanji-specific stats (this logic was in the deprecated action)
        $article->jlptcommon = $article->kanjis->where('jlpt', '-')->count();
        $article->kanjiTotal = collect(['n1', 'n2', 'n3', 'n4', 'n5'])
            ->sum(fn($level) => $article->$level) + $article->jlptcommon;
    }
}
