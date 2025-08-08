<?php
namespace App\Domain\Articles\Actions\Retrieval;

use App\Domain\Articles\DTOs\ArticleListDTO;
use App\Domain\Articles\Models\Article;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Domain\Engagement\Actions\LoadArticleListStatsAction;
use App\Domain\Articles\Actions\Retrieval\LoadArticleListHashtagsAction;

class GetArticlesAction
{
    public function __construct(
        private LoadArticleListStatsAction $loadStats,
        private LoadArticleListHashtagsAction $loadHashtags
    ) {
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
        $articles = $this->buildQuery($articleListDTO)->paginate($articleListDTO->perPage->value);

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

        if ($articleListDTO->category !== null) {
            $query->where('category_id', $articleListDTO->category);
        }

        if ($articleListDTO->hasSearch()) {
            $searchValue = $articleListDTO->getSearchValue();
            $query->where(function($q) use ($searchValue) {
                $q->where('title_jp', 'LIKE', '%' . $searchValue . '%')
                  ->orWhere('title_en', 'LIKE', '%' . $searchValue . '%');
            });
        }

        return $query->orderBy(
            $articleListDTO->sort->field,
            $articleListDTO->sort->direction
        );
    }
}
