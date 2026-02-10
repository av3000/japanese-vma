<?php

namespace App\Application\Articles\Services;

use App\Application\Engagement\Actions\{IncrementViewAction};
use App\Application\Engagement\Services\HashtagServiceInterface;
use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\{HashtagRepositoryInterface, ViewRepositoryInterface, LikeRepositoryInterface, DownloadRepositoryInterface};
use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Application\Articles\Policies\ArticlePolicy;


use App\Application\Articles\Actions\Deletion\{
    CleanupArticleCustomListsAction
};

use App\Application\Articles\Jobs\ProcessArticleKanjisJob;
use App\Application\JapaneseMaterial\Kanjis\Services\KanjiAttachmentService;
use App\Application\JapaneseMaterial\Kanjis\Services\KanjiExtractionServiceInterface;
use App\Application\LastOperations\Interfaces\Repositories\LastOperationRepositoryInterface;
use App\Application\LastOperations\Services\LastOperationServiceInterface;
use App\Domain\Articles\DTOs\{ArticleCreateDTO, ArticleIncludeOptionsDTO, ArticleUpdateDTO, ArticleListDTO, ArticleCriteriaDTO};
use App\Domain\Articles\Models\Article as DomainArticle;
use App\Domain\Articles\Models\Articles;
use App\Domain\Articles\Factories\ArticleFactory;
use App\Domain\Articles\ValueObjects\{ArticleSortCriteria, ArticleTitle, ArticleContent, ArticleSourceUrl};
use App\Domain\Shared\ValueObjects\{UserId, UserName, EntityId, Viewer, Pagination, SearchTerm};
use App\Domain\Articles\Exceptions\{ArticleNotFoundException, ArticleAccessDeniedException};
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Articles\Errors\ArticleErrors;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Shared\Results\Result;

// TODO: gradually replace these with repository pattern and remove the import of direct persistence model
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ArticleService implements ArticleServiceInterface
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
        private LastOperationServiceInterface $lastOperationService,
        private HashtagServiceInterface $hashtagService,
        private ArticlePolicy $ArticlePolicy,
        // Engagement and stats dependencies
        // private ExtractKanjisAction $extractKanjis,
        private IncrementViewAction $incrementViewAction,
        // private ProcessWordMeaningsAction $processWords,
        // private LoadCommentsAction $loadComments,
        // List operations dependencies
        // private LoadStatsAction $loadListStats,
        // private LoadHashtagsAction $loadHashtags,
        // Update dependencies
        // private UpdateArticleHashtagsAction $updateHashtags,
        // Delete dependencies
        private CleanupArticleCustomListsAction $cleanupCustomLists,
        private HashtagRepositoryInterface $hashtagRepository,
        private ViewRepositoryInterface $viewRepository,
        private LikeRepositoryInterface $likeRepository,
        private DownloadRepositoryInterface $downloadRepository,
        private CommentRepositoryInterface $commentRepository,

        // private KanjiExtractionServiceInterface $kanjiExtractionService,
        // private KanjiAttachmentService $kanjiAttachmentService
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
                // TODO: consider if should it be factory or some kind of mapper pattern?
                $domainArticle = ArticleFactory::createFromDTO(
                    $dto,
                    new UserId($user->id),
                    new UserName($user->name)
                );
                // TODO: for frontend we only need UUID/ID which can be used to redirect user to article details page where frontend fetched the article show endpoint.
                $createdDomainArticle = $this->articleRepository->create($domainArticle);

                if ($dto->tags && !empty($dto->tags)) {
                    $hashtagResult = $this->hashtagService->createTagsForEntity(
                        $createdDomainArticle->getIdValue(),
                        ObjectTemplateType::ARTICLE,
                        $dto->tags,
                        $user->id
                    );

                    if ($hashtagResult->isFailure()) {
                        // TODO: consider result pattern, as all system errors/exceptions should be matched and caught in global handler with standard response
                        throw new \Exception($hashtagResult->getError()->description);
                    }
                }

                ProcessArticleKanjisJob::dispatch(
                    $createdDomainArticle->getUid()->value(),
                    $dto->content_jp
                );

                return $createdDomainArticle;
            });

            return Result::success($article);
        } catch (\Exception $e) {
            Log::error('Article creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return Result::failure(ArticleErrors::creationFailed());
        }
    }

    public function getArticleIdByUuid(EntityId $uuid): int
    {
        return $this->articleRepository->getIdByUuid($uuid);
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

        if (!$this->ArticlePolicy->canView($user, $article)) {
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
            Log::error("Failed to increment view for article {$id}: " . $e->getMessage());
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
            visibilityRules: $this->ArticlePolicy->getVisibilityCriteria($user),
            pagination: Pagination::fromInputOrDefault($dto->page, $dto->per_page),
            include_kanjis: $dto->include_kanjis
        );

        return $this->articleRepository->findByCriteria($criteriaDTO);
    }

    /**
     * Update article with optional hashtag and content reprocessing.
     *
     * @param EntityId $articleUid Article UID
     * @param ArticleUpdateDTO $dto Update data
     * @param User $user User for authorized actions
     * @return Result Success data: DomainArticle, Failure data: Error
     * @todo Refactor to use EntityId and return DomainArticle
     */
    public function updateArticle(string $uid, ArticleUpdateDTO $dto, User $user): Result
    {
        $articleUid = EntityId::from($uid);

        try {
            $domainArticle = $this->articleRepository->findByPublicUid($articleUid);

            if (!$domainArticle) {
                return Result::failure(ArticleErrors::notFound($articleUid->value()));
            }

            if (!$this->ArticlePolicy->canUpdate($user, $domainArticle)) {
                return Result::failure(ArticleErrors::accessDenied($articleUid->value()));
            }

            $shouldReprocessContent = $dto->content_jp !== null
                && $dto->content_jp !== $domainArticle->getContentJp()->value;

            $updatedDomainArticle = DB::transaction(function () use ($domainArticle, $dto, $user) {
                $updatedDomainArticle = $this->applyUpdates($domainArticle, $dto);

                $this->articleRepository->update($updatedDomainArticle);

                if ($dto->hashtags !== null) {
                    $hashtagResult = $this->hashtagService->syncTagsForEntity(
                        $domainArticle->getIdValue(),
                        ObjectTemplateType::ARTICLE,
                        $dto->hashtags,
                        $user->id
                    );

                    if ($hashtagResult->isFailure()) {
                        throw new \Exception($hashtagResult->getError()->description);
                    }
                }

                return $updatedDomainArticle;
            });

            if ($shouldReprocessContent) {
                ProcessArticleKanjisJob::dispatch(
                    $updatedDomainArticle->getUid()->value(),
                    $dto->content_jp
                );
            }

            return Result::success($updatedDomainArticle);
        } catch (\Exception $e) {
            Log::error('Article update failed', [
                'user_id' => $user->id,
                'article_uuid' => $articleUid,
                'error' => $e->getMessage(),
            ]);

            return Result::failure(ArticleErrors::updateFailed($e->getMessage()));
        }
    }

    /**
     * Apply DTO updates to domain model, returning new immutable instance.
     * Only updates fields that are present (non-null) in the DTO.
     *
     * @param DomainArticle $article Original domain article
     * @param ArticleUpdateDTO $dto Update data
     * @return DomainArticle New domain article with updated values
     */
    // TODO: shouldnt it belong to some mapper or builder class?
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
            $dto->title_en_present
                ? ($dto->title_en !== null ? new ArticleTitle($dto->title_en) : null)
                : $article->getTitleEn(),
            $dto->content_jp !== null
                ? new ArticleContent($dto->content_jp)
                : $article->getContentJp(),
            $dto->content_en_present
                ? ($dto->content_en !== null ? new ArticleContent($dto->content_en) : null)
                : $article->getContentEn(),
            $dto->source_link !== null
                ? new ArticleSourceUrl($dto->source_link)
                : $article->getSourceUrl(),
            $dto->publicity !== null
                ? ($dto->publicity ? PublicityStatus::PUBLIC : PublicityStatus::PRIVATE)
                : $article->getPublicity(),
            $article->getStatus(),
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

                if (!$this->ArticlePolicy->canDelete($user, $article)) {
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
            Log::error('Article deletion failed', [
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
            perPage: $pagination->per_page,
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
            perPage: $pagination->per_page,
            page: $pagination->page
        );
    }
}
