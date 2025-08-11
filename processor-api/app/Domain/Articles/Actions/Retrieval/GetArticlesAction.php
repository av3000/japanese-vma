<?php
namespace App\Domain\Articles\Actions\Retrieval;

use App\Domain\Articles\DTOs\ArticleListDTO;
use App\Domain\Articles\Models\Article;
use App\Http\User;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Domain\Engagement\Actions\LoadArticleListStatsAction;
use App\Domain\Articles\Actions\Retrieval\LoadArticleListHashtagsAction;
use App\Domain\Articles\Interfaces\Policies\ArticleViewPolicyInterface;
use App\Domain\Articles\Interfaces\Actions\ArticleListActionInterface;
use App\Domain\Articles\Actions\Retrieval\LoadStatsAction;
use App\Domain\Articles\Actions\Retrieval\LoadHashtagsAction;

class GetArticlesAction implements ArticleListActionInterface
{
    public function __construct(
        private LoadStatsAction $loadStats,
        private LoadHashtagsAction $loadHashtags,
        private ArticleViewPolicyInterface $viewPolicy
    ) {}

    /**
     * Get articles with optional data enhancement.
     * This method demonstrates action composition - building complex behavior
     * from focused, single-purpose actions.
     */
    public function execute(
        ArticleListDTO $articleListDTO, ?User $user = null
    ): LengthAwarePaginator {
        $articles = $this->buildQuery($articleListDTO, $user)->paginate($articleListDTO->perPage->value);

        $this->loadHashtags->execute($articles);

        if ($articleListDTO->shouldIncludeStats()) {
            $this->loadStats->execute($articles);
        }

        return $articles;
    }

    /**
     * Build the base query with filters and sorting.
     * This method encapsulates the core article retrieval logic.
     */
    private function buildQuery(ArticleListDTO $articleListDTO, ?User $user)
    {
        $query = Article::query()->with('user');

        $query = $this->viewPolicy->applyVisibilityFilter($query, $user);

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
