<?php
namespace App\Domain\Articles\Actions\Retrieval;

use App\Domain\Articles\DTOs\ArticleListDTO;
use App\Domain\Articles\Models\Article;
use App\Domain\Articles\ValueObjects\ArticleSearchTerm;
use App\Domain\Articles\ValueObjects\ArticleSortCriteria;
use App\Domain\Shared\ValueObjects\PerPageLimit;
use App\Http\User;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Domain\Articles\Actions\Retrieval\LoadStatsAction;
use App\Domain\Articles\Actions\Retrieval\LoadHashtagsAction;
use App\Domain\Articles\Interfaces\Policies\ArticleViewPolicyInterface;
use App\Domain\Articles\Interfaces\Actions\ArticleListActionInterface;

class GetArticlesAction implements ArticleListActionInterface
{
    public function __construct(
        private LoadStatsAction $loadStats,
        private LoadHashtagsAction $loadHashtags,
        private ArticleViewPolicyInterface $viewPolicy
    ) {}

    public function execute(ArticleListDTO $dto, ?User $user = null): LengthAwarePaginator
    {
        // Convert DTO data to Value Objects here (in domain layer)
        $search = $dto->search ? ArticleSearchTerm::fromInput($dto->search) : null;
        $sort = ArticleSortCriteria::fromInputOrDefault($dto->sort_by, $dto->sort_dir);
        $perPage = PerPageLimit::fromInputOrDefault($dto->per_page);

        $articles = $this->buildQuery($dto, $search, $sort, $user)->paginate($perPage->value);

        $this->loadHashtags->execute($articles);

        if ($dto->includeStats) {
            $this->loadStats->execute($articles);
        }

        return $articles;
    }

    private function buildQuery(
        ArticleListDTO $dto,
        ?ArticleSearchTerm $search,
        ArticleSortCriteria $sort,
        ?User $user
    ) {
        $query = Article::query()->with('user');

        $query = $this->viewPolicy->applyVisibilityFilter($query, $user);

        if ($dto->category !== null) {
            $query->where('category_id', $dto->category);
        }

        if ($search !== null) {
            $query->where(function($q) use ($search) {
                $searchValue = $search->value;
                $q->where('title_jp', 'LIKE', '%' . $searchValue . '%')
                  ->orWhere('title_en', 'LIKE', '%' . $searchValue . '%');
            });
        }

        return $query->orderBy($sort->field->value, $sort->direction->value);
    }
}
