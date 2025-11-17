<?php
namespace App\Application\Articles\Services;

use App\Application\Engagement\Actions\{IncrementViewAction, LoadArticleCommentsAction};
use App\Application\Engagement\Services\{EngagementServiceInterface, HashtagServiceInterface};
use App\Application\Articles\Actions\Retrieval\{LoadArticleDetailStatsAction};
// use App\Application\Articles\Actions\Processing\{ExtractKanjisAction};
use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\{HashtagRepositoryInterface, ViewRepositoryInterface, LikeRepositoryInterface, DownloadRepositoryInterface};
use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Application\Articles\Policies\ArticleViewPolicy;

use App\Application\Articles\Actions\Updates\{
    ReprocessArticleDataAction,
    // UpdateArticleHashtagsAction
};

use App\Application\Articles\Actions\Deletion\{
    CleanupArticleHashtagsAction,
    CleanupArticleCustomListsAction
};

use App\Domain\Articles\DTOs\{ArticleCreateDTO, ArticleIncludeOptionsDTO, ArticleUpdateDTO, ArticleListDTO, ArticleCriteriaDTO};
use App\Domain\Articles\Models\Article as DomainArticle;
use App\Domain\Articles\Models\Articles;
use App\Domain\Articles\Factories\ArticleFactory;
use App\Domain\Articles\ValueObjects\{ArticleId, ArticleSortCriteria, ArticleTitle, ArticleContent, ArticleSourceUrl};
use App\Domain\Shared\ValueObjects\{UserId, UserName, EntityId, Viewer, PerPageLimit, Pagination, SearchTerm};
use App\Domain\Articles\Exceptions\{ArticleNotFoundException, ArticleAccessDeniedException};
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Articles\Errors\ArticleErrors;

use App\Shared\Results\Result;

// TODO: gradually replace these with repository pattern and remove the import of direct persistence model
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Http\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ArticleService implements ArticleServiceInterface
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
        private HashtagServiceInterface $hashtagService,
        private EngagementServiceInterface $engagementService,
        private ArticleViewPolicy $articleViewPolicy,
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
        private CleanupArticleHashtagsAction $cleanupHashtags,
        private CleanupArticleCustomListsAction $cleanupCustomLists,
        private HashtagRepositoryInterface $hashtagRepository,
        private ViewRepositoryInterface $viewRepository,
        private LikeRepositoryInterface $likeRepository,
        private DownloadRepositoryInterface $downloadRepository,
        private CommentRepositoryInterface $commentRepository,
    ) {}

    /**
     * Create article with hashtags atomically.
     * Validates hashtags before transaction, creates article and hashtags together.
     *
     * @param ArticleCreateDTO $dto Article data
     * @param User $user Authenticated user
     * @return Result Success data: DomainArticle, Failure data: Error
     */
    public function createArticle(ArticleCreateDTO $dto, User $user): Result
    {
        try {
            $article = DB::transaction(function () use ($dto, $user) {
                $domainArticle = ArticleFactory::createFromDTO(
                    $dto,
                    new UserId($user->id),
                    new UserName($user->name)
                );
                $article = $this->articleRepository->create($domainArticle);

                if ($dto->tags && !empty($dto->tags)) {
                    $hashtagResult = $this->hashtagService->createTagsForEntity(
                        $article->getIdValue(),
                        ObjectTemplateType::ARTICLE,
                        $dto->tags,
                        $user->id
                    );

                    if ($hashtagResult->isFailure()) {
                        throw new \Exception($hashtagResult->error->description);
                    }
                }

                return $article;
            });

            return Result::success($article);

        } catch (\Exception $e) {
            \Log::error('Article creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return Result::failure(ArticleErrors::creationFailed());
        }
    }

    /**
     * Get article by UUID with permission check and view tracking.
     *
     * @param EntityId $articleUid Article UUID
     * @param ArticleIncludeOptionsDTO $dto Eager loading options
     * @param User|null $user Current user
     * @return Result Success data: DomainArticle, Failure data: Error
     */
    public function getArticle(EntityId $articleUid, ArticleIncludeOptionsDTO $dto, ?User $user = null): Result
    {
        $article = $this->articleRepository->findByPublicUid($articleUid, $dto);

        if (!$article) {
            return Result::failure(ArticleErrors::notFound($articleUid->value()));
        }

        if (!$this->articleViewPolicy->canView($user, $article)) {
            return Result::failure(ArticleErrors::accessDenied($articleUid->value()));
        }

        $viewer = new Viewer($user?->id, request()->ip());
        $this->trackView($article->getIdValue(), ObjectTemplateType::ARTICLE, $viewer);

        return Result::success($article);
    }

    /**
     * Track article view (gracefully handles failures).
     *
     * @param int $id Article ID
     * @param ObjectTemplateType $objectTemplateType Entity type
     * @param Viewer $viewer User and IP info
     * @return void
     */
    private function trackView(int $id, ObjectTemplateType $objectTemplateType, Viewer $viewer): void
    {
        try {
            $this->incrementViewAction->execute($id, $objectTemplateType, $viewer);
        } catch (\Exception $e) {
            \Log::error("Failed to increment view for article {$id}: " . $e->getMessage());
        }
    }

    /**
     * Get filtered, sorted, paginated list of articles with permission-based visibility.
     *
     * @param ArticleListDTO $dto Filter criteria
     * @param User|null $user Current user for visibility
     * @return Articles Domain collection with pagination metadata
     */
    public function getArticlesList(ArticleListDTO $dto, ?User $user = null): Articles
    {
        $criteriaDTO = new ArticleCriteriaDTO(
            search: $dto->search !== null ? SearchTerm::fromInputOrNull($dto->search) : null,
            sort: ArticleSortCriteria::fromInputOrDefault($dto->sort_by, $dto->sort_dir),
            categoryId: $dto->category,
            visibilityRules: $this->articleViewPolicy->getVisibilityCriteria($user),
            pagination: Pagination::fromInputOrDefault($dto->page, $dto->per_page)
        );

        return $this->articleRepository->findByCriteria($criteriaDTO);
    }

    /**
     * Update article with optional hashtag and content reprocessing.
     *
     * @param EntityId $articleUid Article UID
     * @param ArticleUpdateDTO $dto Update data
     * @param User $user User for authorized actions
     * @return Result Success data: PersistenceArticle, Failure data: Error
     * @todo Refactor to use EntityId and return DomainArticle
     */
    public function updateArticle(string $uid, ArticleUpdateDTO $dto, User $user): Result
    {
        $articleUid = EntityId::from($uid);

        try {
            $result = DB::transaction(function () use ($articleUid, $dto, $user) {
                $domainArticle = $this->articleRepository->findByPublicUid($articleUid);

                if (!$domainArticle) {
                    return Result::failure(ArticleErrors::notFound($articleUid->value()));
                }

                if (!$this->articleViewPolicy->canView($user, $domainArticle)) {
                    return Result::failure(ArticleErrors::accessDenied($articleUid->value()));
                }

                $updatedDomainArticle = $this->applyUpdates($domainArticle, $dto);

                $this->articleRepository->update($updatedDomainArticle);

                if ($dto->tags !== null) {
                    $hashtagResult = $this->hashtagService->updateTagsForEntity(
                        $domainArticle->getIdValue(),
                        ObjectTemplateType::ARTICLE,
                        $dto->tags,
                        $user->id
                    );

                    if ($hashtagResult->isFailure()) {
                        throw new \Exception($hashtagResult->error->description);
                    }
                }

                // TODO: Add some extra checks to see if kanjis or words has changed.
                if ($this->shouldReprocessContent($dto)) {
                    // TODO: implement kanji processing queueing. Part of live updates with websocket for frontend.
                    // $this->reprocessData->execute($updatedDomainArticle);
                }

                return $updatedDomainArticle;

            });

            return Result::success($result);
        } catch (\Exception $e) {
            \Log::error('Article update failed', [
                'user_id' => $user->id,
                'article_uuid' => $articleUid,
                'error' => $e->getMessage(),
            ]);

            return Result::failure(ArticleErrors::updateFailed($e->getMessage()));
        }
    }

    /**
    // TODO: Might consider moving this method somewhere closer to domain.
     * Determine if content changes require kanji/word reprocessing.
     *
     * @param ArticleUpdateDTO $dto Update data
     * @return bool True if reprocessing needed
     */
    private function shouldReprocessContent(ArticleUpdateDTO $dto): bool
    {
        return $dto->reattach || $dto->content_jp !== null;
    }

    /**
     * Apply DTO updates to domain model, returning new immutable instance.
     * Only updates fields that are present (non-null) in the DTO.
     *
     * @param DomainArticle $article Original domain article
     * @param ArticleUpdateDTO $dto Update data
     * @return DomainArticle New domain article with updated values
     */
    // TODO: shouldnt it belong to some mapper class?
    private function applyUpdates(DomainArticle $article, ArticleUpdateDTO $dto): DomainArticle
    {
        return new DomainArticle(
            $article->getIdValue(),
            $article->getUid(),
            $article->getEntityTypeUid(),
            $article->getAuthorId(),
            $article->getAuthorName(),
            $dto->title_jp !== null
                ? new ArticleTitle($dto->title_jp)
                : $article->getTitleJp(),
            $dto->title_en !== null
                ? ($dto->title_en ? new ArticleTitle($dto->title_en) : null)
                : $article->getTitleEn(),
            $dto->content_jp !== null
                ? new ArticleContent($dto->content_jp)
                : $article->getContentJp(),
            $dto->content_en !== null
                ? ($dto->content_en ? new ArticleContent($dto->content_en) : null)
                : $article->getContentEn(),
            $dto->source_link !== null
                ? new ArticleSourceUrl($dto->source_link)
                : $article->getSourceUrl(),
            $dto->publicity !== null
                ? PublicityStatus::from($dto->publicity)
                : $article->getPublicity(),
            $dto->status !== null
                ? ArticleStatus::from($dto->status)
                : $article->getStatus(),
            $article->getJlptLevels(), // TODO: Recalculate if content changed
            $article->getCreatedAt(),
            now()->toDateTimeImmutable(), // Always update timestamp
        );
    }

    /**
     * Delete article with full cleanup of relationships and engagement data.
     *
     * @param EntityId $articleUuid Article UUID
     * @param User $user User requesting deletion
     * @return Result Success data: null, Failure data: Error
     */
    public function deleteArticle(EntityId $articleUuid, User $user): Result
    {
        try {
            DB::transaction(function () use ($articleUuid, $user) {
                $article = $this->articleRepository->findByPublicUid($articleUuid);

                if (!$article) {
                    throw new ArticleNotFoundException($articleUuid->value());
                }

                if (!$this->articleViewPolicy->canDelete($user, $article)) {
                    throw new ArticleAccessDeniedException($articleUuid->value());
                }

                // Delete relationships and engagement data
                $this->articleRepository->deleteById($article->getIdValue());
                $this->viewRepository->deleteByEntity($article->getIdValue(), ObjectTemplateType::ARTICLE->getLegacyId());
                $this->downloadRepository->deleteByEntity($article->getIdValue(), ObjectTemplateType::ARTICLE->getLegacyId());
                $this->likeRepository->deleteByEntity($article->getIdValue(), ObjectTemplateType::ARTICLE->getLegacyId());
                $this->commentRepository->deleteByEntity($article->getIdValue(), ObjectTemplateType::ARTICLE->getLegacyId());
                $this->hashtagRepository->deleteByEntity($article->getIdValue(), ObjectTemplateType::ARTICLE->getLegacyId());
                $this->cleanupCustomLists->execute($article->getIdValue());
            });

            return Result::success();

        } catch (ArticleNotFoundException $e) {
            return Result::failure(ArticleErrors::notFound($articleUuid->value()));
        } catch (ArticleAccessDeniedException $e) {
            return Result::failure(ArticleErrors::accessDenied($articleUuid->value()));
        } catch (\Exception $e) {
            \Log::error('Article deletion failed', [
                'article_uuid' => $articleUuid->value(),
                'error' => $e->getMessage(),
            ]);
            return Result::failure(ArticleErrors::deletionFailed());
        }
    }

    /**
     * Get paginated kanjis for article.
     *
     * @param int $articleId Article ID
     * @param int|null $page Page number
     * @param int|null $perPage Items per page
     * @return LengthAwarePaginator Eloquent paginator
     */
    public function getArticleKanjis(int $articleId, ?int $page = null, ?int $perPage = null): LengthAwarePaginator
    {
        $pagination = new Pagination($page, $perPage);
        $article = PersistenceArticle::findOrFail($articleId);

        return $article->kanjis()->paginate(
            perPage: $pagination->perPage,
            page: $pagination->page
        );
    }

    /**
     * Get paginated words for article.
     *
     * @param int $articleId Article ID
     * @param int|null $page Page number
     * @param int|null $perPage Items per page
     * @return LengthAwarePaginator Eloquent paginator
     */
    public function getArticleWords(int $articleId, ?int $page = null, ?int $perPage = null): LengthAwarePaginator
    {
        $pagination = new Pagination($page, $perPage);
        $article = PersistenceArticle::findOrFail($articleId);

        return $article->words()->paginate(
            perPage: $pagination->perPage,
            page: $pagination->page
        );
    }
}
