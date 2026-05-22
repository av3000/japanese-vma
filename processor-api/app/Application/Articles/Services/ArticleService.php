<?php

namespace App\Application\Articles\Services;

use App\Application\Articles\Actions\Deletion\CleanupArticleCustomListsAction;
use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Application\Articles\Jobs\ProcessArticleKanjisJob;
use App\Application\Articles\Policies\ArticlePolicy;
use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Application\Engagement\Actions\IncrementViewAction;
use App\Application\Engagement\Interfaces\Repositories\DownloadRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\HashtagRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\LikeRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\ViewRepositoryInterface;
use App\Application\Engagement\Services\EngagementServiceInterface;
use App\Application\Engagement\Services\HashtagServiceInterface;
use App\Application\LastOperations\Services\LastOperationServiceInterface;
use App\Domain\Articles\DTOs\ArticleCreateDTO;
use App\Domain\Articles\DTOs\ArticleCriteriaDTO;
use App\Domain\Articles\DTOs\ArticleDetailResultDTO;
use App\Domain\Articles\DTOs\ArticleIncludeOptionsDTO;
use App\Domain\Articles\DTOs\ArticleListDTO;
use App\Domain\Articles\DTOs\ArticleListItemDTO;
use App\Domain\Articles\DTOs\ArticleListResultDTO;
use App\Domain\Articles\DTOs\ArticleUpdateDTO;
use App\Domain\Articles\DTOs\ArticleUpdateResultDTO;
use App\Domain\Articles\Errors\ArticleErrors;
use App\Domain\Articles\Exceptions\ArticleAccessDeniedException;
use App\Domain\Articles\Exceptions\ArticleNotFoundException;
use App\Domain\Articles\Factories\ArticleFactory;
use App\Domain\Articles\Models\Article as DomainArticle;
use App\Domain\Articles\ValueObjects\ArticleContent;
use App\Domain\Articles\ValueObjects\ArticleSortCriteria;
use App\Domain\Articles\ValueObjects\ArticleSourceUrl;
use App\Domain\Articles\ValueObjects\ArticleTitle;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Domain\Shared\ValueObjects\SearchTerm;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;
use App\Domain\Shared\ValueObjects\Viewer;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Models\LastOperationState;
use App\Infrastructure\Persistence\Models\User;
use App\Shared\Results\Result;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ArticleService implements ArticleServiceInterface
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
        private HashtagServiceInterface $hashtagService,
        private EngagementServiceInterface $engagementService,
        private LastOperationServiceInterface $lastOperationService,
        private ArticlePolicy $articlePolicy,
        private IncrementViewAction $incrementViewAction,
        private CleanupArticleCustomListsAction $cleanupCustomLists,
        private HashtagRepositoryInterface $hashtagRepository,
        private ViewRepositoryInterface $viewRepository,
        private LikeRepositoryInterface $likeRepository,
        private DownloadRepositoryInterface $downloadRepository,
        private CommentRepositoryInterface $commentRepository
    ) {
    }

    /**
     * Create article with hashtags atomically.
     * Validates hashtags before transaction, creates article and hashtags together.
     *
     * @param ArticleCreateDTO $dto Article data
     * @param User $user Authenticated user
     *
     * @return Result Success data: DomainArticle, Failure data: ResultError
     */
    public function createArticle(ArticleCreateDTO $dto, User $user): Result
    {
        try {
            $article = DB::transaction(function () use ($dto, $user) {
                // TODO: consider if should it be factory or some kind of mapper pattern?
                $domainArticle = ArticleFactory::createFromDTO(
                    $dto,
                    new UserId($user->id),
                    new UserName($user->name),
                    new EntityId($user->uuid)
                );
                // TODO: for frontend we only need UUID/ID which can be used to redirect user to article details page where frontend fetched the article show endpoint.
                $createdDomainArticle = $this->articleRepository->create($domainArticle);

                if ($dto->tags && ! empty($dto->tags)) {
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

    public function getArticleIdByUuid(EntityId $uuid): ?int
    {
        return $this->articleRepository->getIdByUuid($uuid);
    }

    /**
     * Get article by UUID with permission check and view tracking.
     *
     * @param EntityId $articleUid Article UUID
     * @param ArticleIncludeOptionsDTO $dto Eager loading options
     * @param User|null $user Current user
     *
     * @return Result Success data: ArticleDetailResultDTO, Failure data: ResultError
     */
    public function getArticle(EntityId $articleUid, ArticleIncludeOptionsDTO $dto, ?User $user = null): Result
    {
        $article = $this->articleRepository->findByPublicUid($articleUid, $dto);

        if (! $article) {
            return Result::failure(ArticleErrors::notFound($articleUid->value()));
        }

        if (! $this->articlePolicy->canView($user, $article)) {
            return Result::failure(ArticleErrors::accessDenied($articleUid->value()));
        }

        $viewer = new Viewer($user?->id, request()->ip());
        $this->trackView($article->getIdValue(), ObjectTemplateType::ARTICLE, $viewer);

        $engagement = $this->engagementService->getSingleArticleEngagementSummary(
            $article->getIdValue(),
            ObjectTemplateType::ARTICLE,
            $dto,
            $user !== null,
        );

        $hashtags = $this->hashtagService->getHashtags(
            $article->getIdValue(),
            ObjectTemplateType::ARTICLE
        );

        $lastOperation = $this->lastOperationService->getLatestState(
            $article->getUid(),
            'kanji_extraction'
        );

        // TODO: move article kanji/word loading to separate paginated uuid-based endpoints
        // once detail payload should stop carrying full lists.
        return Result::success(new ArticleDetailResultDTO(
            article: $article,
            engagement: $engagement,
            kanjis: $article->getKanjis(),
            words: $article->getWords(),
            hashtags: $hashtags,
            lastOperation: $lastOperation,
        ));
    }

    /**
     * Track article view (gracefully handles failures).
     *
     * @param int $id Article ID
     * @param ObjectTemplateType $objectTemplateType Entity type
     * @param Viewer $viewer User and IP info
     */
    private function trackView(int $id, ObjectTemplateType $objectTemplateType, Viewer $viewer): void
    {
        try {
            $this->incrementViewAction->execute($id, $objectTemplateType, $viewer);
        } catch (\Exception $e) {
            Log::error('Failed to increment article view', [
                'article_id' => $id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get filtered, sorted, paginated list of articles with permission-based visibility.
     *
     * @param ArticleListDTO $dto Filter criteria
     * @param User|null $user Current user for visibility
     *
     * @return ArticleListResultDTO Shaped domain collection with pagination metadata
     */
    public function getArticlesList(ArticleListDTO $dto, ?User $user = null): ArticleListResultDTO
    {
        // TODO: Perhaps this should follow some filter builder pattern, or this is passing this responsibility to repository?
        $criteriaDTO = new ArticleCriteriaDTO(
            search: $dto->search !== null ? SearchTerm::fromInputOrNull($dto->search) : null,
            sort: ArticleSortCriteria::fromInputOrDefault($dto->sort_by, $dto->sort_dir),
            categoryId: $dto->category,
            authorUid: $dto->author_uid,
            visibilityRules: $this->articlePolicy->getVisibilityCriteria($user),
            pagination: Pagination::fromInputOrDefault($dto->page, $dto->per_page),
            include_kanjis: $dto->include_kanjis
        );

        $paginatedArticles = $this->articleRepository->findByCriteria($criteriaDTO);
        $articles = $paginatedArticles->getItems();
        $articleIds = array_map(
            static fn (DomainArticle $article): int => $article->getIdValue(),
            $articles,
        );
        $articleUuids = array_map(
            static fn (DomainArticle $article): string => $article->getUid()->value(),
            $articles,
        );

        $statsMap = $dto->include_stats_counts
            ? $this->engagementService->enhanceArticlesWithStatsCounts($paginatedArticles)
            : [];

        // TODO: IndexArticleRequest still does not validate/normalize include_hashtags,
        // so article-list hashtags remain effectively always-on until a follow-up cleanup.
        $hashtagsMap = $dto->include_hashtags
            ? $this->hashtagService->getBatchHashtags($articleIds, ObjectTemplateType::ARTICLE)
            : [];

        /** @var array<string, LastOperationState> $lastOperationsMap */
        $lastOperationsMap = $articleUuids === []
            ? []
            : $this->lastOperationService->getBatchLatestStates($articleUuids, 'kanji_extraction');

        $paginator = $paginatedArticles->getPaginator();

        return new ArticleListResultDTO(
            items: array_map(
                static fn (DomainArticle $article): ArticleListItemDTO => new ArticleListItemDTO(
                    article: $article,
                    stats: $statsMap[$article->getIdValue()] ?? null,
                    hashtags: $hashtagsMap[$article->getIdValue()] ?? [],
                    lastOperation: $lastOperationsMap[$article->getUid()->value()] ?? null,
                ),
                $articles,
            ),
            pagination: [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
            ],
            include_hashtags: $dto->include_hashtags,
            include_stats: $dto->include_stats_counts,
        );
    }

    /**
     * Update article with optional hashtag and content reprocessing.
     *
     * @param string $uid Article UUID
     * @param ArticleUpdateDTO $dto Update data
     * @param User $user User for authorized actions
     *
     * @return Result Success data: ArticleUpdateResultDTO, Failure data: ResultError
     *
     * @todo Refactor to use EntityId.
     */
    public function updateArticle(string $uid, ArticleUpdateDTO $dto, User $user): Result
    {
        $articleUid = EntityId::from($uid);

        try {
            $domainArticle = $this->articleRepository->findByPublicUid($articleUid);

            if (! $domainArticle) {
                return Result::failure(ArticleErrors::notFound($articleUid->value()));
            }

            if (! $this->articlePolicy->canUpdate($user, $domainArticle)) {
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

            return Result::success(
                new ArticleUpdateResultDTO(
                    article: $updatedDomainArticle,
                    hashtags: $this->hashtagService->getHashtags(
                        $updatedDomainArticle->getIdValue(),
                        ObjectTemplateType::ARTICLE
                    ),
                )
            );
        } catch (\Exception $e) {
            Log::error('Article update failed', [
                'user_id' => $user->id,
                'article_uuid' => $articleUid->value(),
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
     *
     * @return DomainArticle New domain article with updated values
     */
    private function applyUpdates(DomainArticle $article, ArticleUpdateDTO $dto): DomainArticle
    {
        return new DomainArticle(
            $article->getIdValue(),
            $article->getUid(),
            $article->getEntityTypeUid(),
            $article->getAuthorId(),
            $article->getAuthorName(),
            $article->getAuthorUuid(),
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
     *
     * @return Result Success data: null, Failure data: ResultError
     */
    public function deleteArticle(EntityId $articleUuid, User $user): Result
    {
        try {
            DB::transaction(function () use ($articleUuid, $user) {
                $article = $this->articleRepository->findByPublicUid($articleUuid);

                if (! $article) {
                    throw new ArticleNotFoundException($articleUuid->value());
                }

                if (! $this->articlePolicy->canDelete($user, $article)) {
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
     *
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
     * Get paginated words for article with typed failure handling.
     *
     * @param int $articleId Article ID
     * @param int|null $page Page number
     * @param int|null $perPage Items per page
     *
     * @return Result Success data: LengthAwarePaginator, Failure data: ResultError
     */
    public function getArticleWordsResult(int $articleId, ?int $page = null, ?int $perPage = null): Result
    {
        try {
            $pagination = Pagination::fromInputOrDefault($page, $perPage);
            $paginator = $this->articleRepository->findWordPaginatorByArticleId($articleId, $pagination);

            if ($paginator === null) {
                return Result::failure(ArticleErrors::notFound((string) $articleId));
            }

            return Result::success($paginator);
        } catch (\Exception $e) {
            Log::error('Article words fetch failed', [
                'article_id' => $articleId,
                'error' => $e->getMessage(),
            ]);

            return Result::failure(ArticleErrors::wordsFetchFailed());
        }
    }

    /**
     * Get paginated words for article.
     *
     * @param int $articleId Article ID
     * @param int|null $page Page number
     * @param int|null $perPage Items per page
     *
     * @return LengthAwarePaginator Eloquent paginator
     */
    public function getArticleWords(int $articleId, ?int $page = null, ?int $perPage = null): LengthAwarePaginator
    {
        $pagination = Pagination::fromInputOrDefault($page, $perPage);
        $paginator = $this->articleRepository->findWordPaginatorByArticleId($articleId, $pagination);

        if ($paginator === null) {
            throw new ModelNotFoundException;
        }

        return $paginator;
    }
}
