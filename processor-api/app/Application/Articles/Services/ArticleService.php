<?php
namespace App\Application\Articles\Services;

use App\Application\Articles\DTOs\{CreateArticleRequest, UpdateArticleRequest, ArticleListRequest};
use App\Application\Articles\Interfaces\ArticleRepositoryInterface;
use App\Application\Articles\Interfaces\ArticleViewPolicyInterface;
use App\Domain\Articles\Entities\Article;
use App\Domain\Articles\ValueObjects\{ArticleId, ArticleSearchTerm, ArticleSortCriteria};
use App\Domain\Shared\ValueObjects\{UserId, PerPageLimit, PaginationData};
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Http\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ArticleService implements ArticleServiceInterface
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
        private ArticleViewPolicyInterface $viewPolicy,
        // Engagement and stats dependencies
        private IncrementViewAction $incrementView,
        private LoadArticleDetailStatsAction $loadStats,
        private ProcessWordMeaningsAction $processWords,
        private LoadArticleCommentsAction $loadComments,
        // List operations dependencies
        private LoadStatsAction $loadListStats,
        private LoadHashtagsAction $loadHashtags,
        // Update dependencies
        private UpdateArticleHashtagsAction $updateHashtags,
        private ReprocessArticleDataAction $reprocessData,
        // Delete dependencies
        private CleanupArticleRelationshipsAction $cleanupRelationships,
        private CleanupArticleEngagementAction $cleanupEngagement,
        private CleanupArticleHashtagsAction $cleanupHashtags,
        private CleanupArticleCustomListsAction $cleanupCustomLists
    ) {}

    public function createArticle(ArticleCreateDTO $dto, int $userId): Article
    {
        // Create rich domain object with all business rules applied
        $authorId = UserId::from($userId);

        $domainArticle = new Article($request->toDTO(), $authorId);

        // Convert domain model to persistence format
        $persistenceData = $domainArticle->toPersistenceData();

        // Repository handles pure data persistence
        $this->articleRepository->save(
            $persistenceData,
            $domainArticle->kanjiIds(),
            $request->tags
        );

        return $domainArticle;
    }

    public function getArticle(int $id, ?int $userId = null): ?Article
    {

        $articleId = ArticleId::from($id);
        $userIdVO = $userId ? UserId::from($userId) : null;

        $persistenceArticle = PersistenceArticle::with(['user', 'kanjis', 'words'])->find($id->value());

        if (!$persistenceArticle) {
            throw new ArticleNotFoundException("Article {$id} not found");
        }

        $user = $userIdVO ? User::find($userIdVO->value()) : null;
        if (!$this->viewPolicy->canView($user, $persistenceArticle)) {
            throw new ArticleAccessDeniedException("Access denied to article {$id}");
        }

        $this->incrementView->execute($persistenceArticle);
        $this->loadStats->execute($persistenceArticle);
        // $this->processWords->execute($persistenceArticle);
        $this->loadComments->execute($persistenceArticle);

        // Convert to domain model (placeholder - needs implementation)
        // return $this->toDomainModel($persistenceArticle);

        return $persistenceArticle;
    }

    public function getArticles(ArticleListDTO $dto, ?User $user = null): LengthAwarePaginator
    {
        $search = $dto->search ? ArticleSearchTerm::fromInput($dto->search) : null;
        $sort = ArticleSortCriteria::fromInputOrDefault($dto->sort_by, $dto->sort_dir);
        $perPage = PerPageLimit::fromInputOrDefault($dto->per_page);

        $query = $this->buildArticlesQuery($dto, $search, $sort, $user);
        $articles = $query->paginate($perPage->value);

        $this->loadHashtags->execute($articles);

        if ($dto->includeStats) {
            $this->loadListStats->execute($articles);
        }

        return $articles;
    }

    public function updateArticle(int $id, ArticleUpdateDTO $dto, int $userId): ?Article
    {
        $articleId = ArticleId::from($id);
        $userIdVO = UserId::from($userId);

        return DB::transaction(function () use ($articleId, $dto, $userIdVO) {
            $article = PersistenceArticle::where('id', $articleId->value())
                ->where('user_id', $userIdVO->value())
                ->first();

            if (!$article) {
                return null;
            }

            \Log::info('Update start for article: ' . $articleId->value());

            $article->updateFromDTO($dto);

            if ($dto->tags !== null) {
                $this->updateHashtags->execute($article, $dto->tags);
            }

            // Reprocess content if needed
            if ($article->shouldReprocessContent($dto)) {
                $this->reprocessData->execute($article);
            }

            // TODO: implement repository pattern
            // $this->articleRepository->save($article)
            $article->save();

            // return $this->toDomainModel($article->fresh(['kanjis', 'user']));
            return $article->fresh(['kanjis', 'user']);
        });
    }

    public function deleteArticle(int $id, UserId $userId, bool $isAdmin = false): bool
    {
        $articleId = ArticleId::from($id);
        $userIdVO = UserId::from($userId);

        return DB::transaction(function () use ($articleId, $userIdVO, $isAdmin) {
            // TODO: repository pattern
            // $article = $this->articleRepository->findById($id);
            $article = PersistenceArticle::find($articleId->value());

            if (!$article) {
                return false;
            }

            // Check authorization
            if ($article->user_id !== $userIdVO->value() && !$isAdmin) {
                return false;
            }

            // Perform cleanup operations
            $this->cleanupRelationships->execute($article);
            $this->cleanupEngagement->execute($article);
            $this->cleanupHashtags->execute($article);
            $this->cleanupCustomLists->execute($article);

            // TODO: repository pattern
            // $this->articleRepository->delete($article);
            $article->delete();
            // TODO: eloquent delete returns true if successful, false otherwise, but need to test if thats correct.
            return true;
        });
    }

    public function getArticleKanjis(int $articleId, ?int $page = null, ?int $perPage = null): LengthAwarePaginator
    {
        $pagination = new PaginationData($page, $perPage);

        $article = PersistenceArticle::findOrFail($articleId);

        return $article->kanjis()
            ->paginate(
                perPage: $pagination->perPage,
                page: $pagination->page
            );
    }

    public function getArticleWords(int $articleId, ?int $page = null, ?int $perPage = null): LengthAwarePaginator
    {
        $pagination = new PaginationData($page, $perPage);

        $article = PersistenceArticle::findOrFail($articleId);

        return $article->words()
            ->paginate(
                perPage: $pagination->perPage,
                page: $pagination->page
            );
    }

    /**
     * Build query for articles list with filters and permissions
     */
    private function buildArticlesQuery(
        ArticleListDTO $dto,
        ?ArticleSearchTerm $search,
        ArticleSortCriteria $sort,
        ?User $user
    ) {
        $query = PersistenceArticle::query()->with('user');

        $query = $this->accessPolicy->applyVisibilityFilter($query, $user);

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

    /**
     * Convert persistence model to domain model
     * TODO: figure if needed, and if so Implement proper conversion logic
     */
    private function toDomainModel(PersistenceArticle $persistenceArticle): Article
    {
        // This needs to be implemented based on your domain model constructor
        // For now, placeholder
        throw new \Exception('Domain model conversion not implemented yet');
    }
}
