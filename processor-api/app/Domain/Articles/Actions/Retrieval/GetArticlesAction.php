<?php
namespace App\Domain\Articles\Actions;

use App\Domain\Articles\DTOs\ArticleListDTO;
use App\Domain\Articles\Models\Article;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Domain\Articles\Actions\LoadArticleListStatsAction;
use App\Domain\Articles\Actions\LoadArticleListHashtagsAction;

class GetArticlesAction
{
    public function __construct(
        private LoadArticleListStatsAction $loadStats,
        private LoadArticleListHashtagsAction $loadHashtags
    ) {
        // Inject both actions so we can use them independently
    }

    /**
     * Get articles with optional data enhancement.
     * This method demonstrates action composition - building complex behavior
     * from focused, single-purpose actions.
     */
    public function execute(
        ArticleListDTO $articleListDTO,
        bool $includeStats = false,
    ): LengthAwarePaginator {
        // Step 1: Get the basic article data with relationships
        $articles = $this->buildQuery($articleListDTO)->paginate($articleListDTO->perPage);

        $this->loadHashtags->execute($articles);

        if ($includeStats) {
            $this->loadStats->execute($articles);
        }

        return $articles;
    }

    /**
     * Build the base query with filters and sorting.
     * This method encapsulates the core article retrieval logic.
     */
    private function buildQuery(ArticleListDTO $articleListDTO)
    {
        $query = Article::query()
            ->where('publicity', 1)
            ->with('user'); // Always load user to avoid N+1 queries

        // Apply optional category filter
        if ($articleListDTO->category !== null) {
            $query->where('category_id', $articleListDTO->category);
        }

        // Apply optional search filter across both language fields
        if ($articleListDTO->search !== null) {
            $query->where(function($q) use ($articleListDTO) {
                $q->where('title_jp', 'LIKE', '%' . $articleListDTO->search . '%')
                  ->orWhere('title_en', 'LIKE', '%' . $articleListDTO->search . '%');
            });
        }

        return $query->orderBy($articleListDTO->sortBy, $articleListDTO->sortDir);
    }
}
