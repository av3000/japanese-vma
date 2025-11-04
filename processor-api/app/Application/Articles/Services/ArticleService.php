<?php
namespace App\Application\Articles\Services;

use App\Application\Engagement\Actions\{IncrementViewAction, LoadArticleCommentsAction};
use App\Application\Engagement\Services\EngagementServiceInterface;
use App\Application\Articles\Actions\Retrieval\{LoadArticleDetailStatsAction};
// use App\Application\Articles\Actions\Processing\{ExtractKanjisAction};
use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Application\Articles\Policies\ArticleViewPolicy;

use App\Application\Articles\Actions\Updates\{
    ReprocessArticleDataAction,
    // UpdateArticleHashtagsAction
};

use App\Application\Articles\Actions\Deletion\{
    CleanupArticleRelationshipsAction,
    CleanupArticleEngagementAction,
    CleanupArticleHashtagsAction,
    CleanupArticleCustomListsAction
};

use App\Domain\Articles\DTOs\{ArticleCreateDTO, ArticleIncludeOptionsDTO, ArticleUpdateDTO, ArticleListDTO, ArticleCriteriaDTO};
use App\Domain\Articles\Models\Article as DomainArticle;
use App\Domain\Articles\Models\Articles;
use App\Domain\Articles\Factories\ArticleFactory;
use App\Domain\Articles\ValueObjects\{ArticleId, ArticleSortCriteria};
use App\Domain\Shared\ValueObjects\{UserId, UserName, EntityId, Viewer, PerPageLimit, Pagination, SearchTerm};
use App\Domain\Articles\Exceptions\{ArticleNotFoundException, ArticleAccessDeniedException};
use App\Domain\Shared\Enums\ObjectTemplateType;
// TODO: gradually replace these with repository pattern and remove the import of direct persistence model
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Http\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ArticleService implements ArticleServiceInterface
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
        private EngagementServiceInterface $engagementService,
        private ArticleViewPolicy $viewPolicy,
        // Engagement and stats dependencies
        // private ExtractKanjisAction $extractKanjis,
        private IncrementViewAction $incrementViewAction,
        private LoadArticleDetailStatsAction $loadStats,
        // private ProcessWordMeaningsAction $processWords,
        // private LoadCommentsAction $loadComments,
        // List operations dependencies
        // private LoadStatsAction $loadListStats,
        // private LoadHashtagsAction $loadHashtags,
        // Update dependencies
        // private UpdateArticleHashtagsAction $updateHashtags,
        private ReprocessArticleDataAction $reprocessData,
        // Delete dependencies
        private CleanupArticleRelationshipsAction $cleanupRelationships,
        private CleanupArticleEngagementAction $cleanupEngagement,
        private CleanupArticleHashtagsAction $cleanupHashtags,
        private CleanupArticleCustomListsAction $cleanupCustomLists
    ) {}

    public function createArticle(ArticleCreateDTO $dto, int $userId): DomainArticle
    {
        return DB::transaction(function () use ($dto, $userId) {
            $authorId = UserId::from($userId);
            // TODO: move to user repository, or potentially get a name from auth context to avoid calling the DB here
            $user = User::findOrFail($userId);
            $authorName = new UserName($user->name);
            $domainArticle = ArticleFactory::createFromDTO($dto, $authorId, $authorName);

            return $this->articleRepository->save($domainArticle);
        });
    }

    public function getArticle(EntityId $articleUid, ArticleIncludeOptionsDTO $dto, ?User $user = null): ?DomainArticle
    {
        $article = $this->articleRepository->findByPublicUid($articleUid, $dto);

        if (!$article) {
            throw new ArticleNotFoundException($articleUid->value());
        }

        if(!$this->viewPolicy->canView($user, $article)) {
            throw new ArticleAccessDeniedException($articleUid->value());
        }

        $viewer = Viewer::fromRequest();

        $this->trackView($article->getIdValue(), ObjectTemplateType::ARTICLE, $viewer);

        return $article;
    }

    // TODO: make purely separate service method with no implementation details spilling here
    private function trackView(int $id, ObjectTemplateType $objectTemplateType, Viewer $viewer): void
    {
        try {
            $this->incrementViewAction->execute($id, $objectTemplateType, $viewer);
        } catch (\Exception $e) {
            \Log::error("Failed to increment view for article {$id}: " . $e->getMessage());
        }
    }

    public function getArticlesList(ArticleListDTO $dto, ?User $user = null): Articles
    {
        // TODO: figure if this could be refactored to some query builder pattern, which then would use mapper to communicate with repository
        $criteriaDTO = new ArticleCriteriaDTO(
            search: $dto->search !== null ? SearchTerm::fromInputOrNull($dto->search) : null,
            sort: ArticleSortCriteria::fromInputOrDefault($dto->sort_by, $dto->sort_dir),
            categoryId: $dto->category !== null ? $dto->category : null,
            visibilityRules: $this->viewPolicy->getVisibilityCriteria($user),
            pagination: Pagination::fromInputOrDefault($dto->page, $dto->per_page)
        );

        return $this->articleRepository->findByCriteria($criteriaDTO);
    }

    public function getArticleIdByUuid(string $articleUuid): int | null
    {
        return $this->articleRepository->getIdByUuid(new EntityId($articleUuid));
    }

    // TODO: refactor to purely clean architecture with repository without leaking persistence data
    public function updateArticle(int $id, ArticleUpdateDTO $dto, int $userId): ?PersistenceArticle
    {
        $articleId = EntityId::from($id);
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

    public function deleteArticle(int $id, int $userId, bool $isAdmin = false): bool
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
        $pagination = new Pagination($page, $perPage);

        $article = PersistenceArticle::findOrFail($articleId);

        return $article->kanjis()
            ->paginate(
                perPage: $pagination->perPage,
                page: $pagination->page
            );
    }

    public function getArticleWords(int $articleId, ?int $page = null, ?int $perPage = null): LengthAwarePaginator
    {
        $pagination = new Pagination($page, $perPage);

        $article = PersistenceArticle::findOrFail($articleId);

        return $article->words()
            ->paginate(
                perPage: $pagination->perPage,
                page: $pagination->page
            );
    }
}
