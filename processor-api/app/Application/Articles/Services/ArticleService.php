<?php

namespace App\Application\Articles\Services;

use App\Application\Articles\Actions\Deletion\CleanupArticleCustomListsAction;
use App\Application\Articles\Interfaces\Readers\ArticleProcessingStateReaderInterface;
use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Application\Articles\Jobs\ProcessArticleContentJob;
use App\Application\Articles\Policies\ArticlePolicy;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Application\Engagement\Actions\IncrementViewAction;
use App\Application\Engagement\Interfaces\Repositories\DownloadRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\HashtagRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\LikeRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\ViewRepositoryInterface;
use App\Application\Engagement\Services\EngagementServiceInterface;
use App\Application\Engagement\Services\HashtagServiceInterface;
use App\Application\Processing\Services\ProcessingStateServiceInterface;
use App\Domain\Articles\DTOs\ArticleCreateDTO;
use App\Domain\Articles\DTOs\ArticleCreateResultDTO;
use App\Domain\Articles\DTOs\ArticleDetailResultDTO;
use App\Domain\Articles\DTOs\ArticleIncludeOptionsDTO;
use App\Domain\Articles\DTOs\ArticleUpdateDTO;
use App\Domain\Articles\DTOs\ArticleUpdateResultDTO;
use App\Domain\Articles\Errors\ArticleErrors;
use App\Domain\Articles\Exceptions\ArticleAccessDeniedException;
use App\Domain\Articles\Exceptions\ArticleNotFoundException;
use App\Domain\Articles\Factories\ArticleFactory;
use App\Domain\Articles\Models\Article as DomainArticle;
use App\Domain\Articles\ValueObjects\ArticleContent;
use App\Domain\Articles\ValueObjects\ArticleSourceUrl;
use App\Domain\Articles\ValueObjects\ArticleTitle;
use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\Enums\ProcessingTaskType;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Domain\Shared\ValueObjects\Viewer;
use App\Shared\Results\Result;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ArticleService implements ArticleServiceInterface
{
    /** Value of `articles.content_version` on a freshly created row (column default). */
    private const INITIAL_CONTENT_VERSION = 1;

    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
        private HashtagServiceInterface $hashtagService,
        private EngagementServiceInterface $engagementService,
        private ArticleProcessingStateReaderInterface $processingStateReader,
        private ArticlePolicy $articlePolicy,
        private IncrementViewAction $incrementViewAction,
        private CleanupArticleCustomListsAction $cleanupCustomLists,
        private HashtagRepositoryInterface $hashtagRepository,
        private ViewRepositoryInterface $viewRepository,
        private LikeRepositoryInterface $likeRepository,
        private DownloadRepositoryInterface $downloadRepository,
        private CommentRepositoryInterface $commentRepository,
        private ProcessingStateServiceInterface $processingStates,
    ) {
    }

    /**
     * Create article with hashtags atomically.
     * Validates hashtags before transaction, creates article and hashtags together.
     *
     * @param ArticleCreateDTO $dto Article data
     *
     * @return Result Success data: DomainArticle, Failure data: ResultError
     */
    public function createArticle(ArticleCreateDTO $dto, AuthenticatedUser $authenticatedUser): Result
    {
        try {
            /** @var ArticleCreateResultDTO $created */
            $created = DB::transaction(function () use ($dto, $authenticatedUser): ArticleCreateResultDTO {
                // TODO: consider if should it be factory or some kind of mapper pattern?
                $domainArticle = ArticleFactory::createFromDTO(
                    $dto,
                    $authenticatedUser->id,
                    $authenticatedUser->name,
                    $authenticatedUser->uuid,
                );
                // TODO: for frontend we only need UUID/ID which can be used to redirect user to article details page where frontend fetched the article show endpoint.
                $createdDomainArticle = $this->articleRepository->create($domainArticle);

                if ($dto->tags && ! empty($dto->tags)) {
                    $hashtagResult = $this->hashtagService->createTagsForEntity(
                        $createdDomainArticle->getIdValue(),
                        ObjectTemplateType::ARTICLE,
                        $dto->tags,
                        $authenticatedUser->id->value(),
                    );

                    if ($hashtagResult->isFailure()) {
                        // TODO: consider result pattern, as all system errors/exceptions should be matched and caught in global handler with standard response
                        throw new \Exception($hashtagResult->getError()->description);
                    }
                }

                // The processing row exists as `pending` before the response is sent, in the same
                // transaction as the article itself (ADR 0001, point 4).
                $processingState = $this->processingStates->startOrReset(
                    ProcessingEntityType::Article,
                    $createdDomainArticle->getUid(),
                    ProcessingTaskType::ArticleContentProcessing,
                    self::INITIAL_CONTENT_VERSION,
                );

                return new ArticleCreateResultDTO($createdDomainArticle, $processingState);
            });

            // ShouldQueueAfterCommit plus `after_commit` on the queue connections: the worker can
            // never observe the article before it is committed.
            ProcessArticleContentJob::dispatch($created->article->getUid()->value(), self::INITIAL_CONTENT_VERSION);

            return Result::success($created);
        } catch (\Exception $e) {
            Log::error('Article creation failed', [
                'user_id' => $authenticatedUser->id->value(),
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
     *
     * @return Result Success data: ArticleDetailResultDTO, Failure data: ResultError
     */
    public function getArticle(
        EntityId $articleUid,
        ArticleIncludeOptionsDTO $dto,
        Viewer $viewer,
        ?AuthenticatedUser $authenticatedUser = null,
    ): Result {
        $article = $this->articleRepository->findByPublicUid($articleUid, $dto);

        if (! $article) {
            return Result::failure(ArticleErrors::notFound($articleUid->value()));
        }

        if (! $this->articlePolicy->canView($authenticatedUser, $article)) {
            return Result::failure(ArticleErrors::accessDenied($articleUid->value()));
        }

        $this->trackView($article->getIdValue(), ObjectTemplateType::ARTICLE, $viewer);

        $engagement = $this->engagementService->getSingleArticleEngagementSummary(
            $article->getIdValue(),
            ObjectTemplateType::ARTICLE,
            $dto,
            $authenticatedUser?->id->value(),
        );

        $hashtags = $this->hashtagService->getHashtags(
            $article->getIdValue(),
            ObjectTemplateType::ARTICLE
        );

        $processingState = $this->processingStateReader->currentState($article->getUid()->value());

        // TODO: move article kanji/word loading to separate paginated uuid-based endpoints
        // once detail payload should stop carrying full lists.
        return Result::success(new ArticleDetailResultDTO(
            article: $article,
            engagement: $engagement,
            kanjis: $article->getKanjis(),
            words: $article->getWords(),
            hashtags: $hashtags,
            processingState: $processingState,
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
     * Update article with optional hashtag and content reprocessing.
     *
     * @param string $uid Article UUID
     * @param ArticleUpdateDTO $dto Update data
     *
     * @return Result Success data: ArticleUpdateResultDTO, Failure data: ResultError
     *
     * @todo Refactor to use EntityId.
     */
    public function updateArticle(string $uid, ArticleUpdateDTO $dto, AuthenticatedUser $authenticatedUser): Result
    {
        $articleUid = EntityId::from($uid);

        try {
            $domainArticle = $this->articleRepository->findByPublicUid($articleUid);

            if (! $domainArticle) {
                return Result::failure(ArticleErrors::notFound($articleUid->value()));
            }

            if (! $this->articlePolicy->canUpdate($authenticatedUser, $domainArticle)) {
                return Result::failure(ArticleErrors::accessDenied($articleUid->value()));
            }

            // Kanji come from the content, words from title + content: either field changing
            // means the derived data is stale and one consolidated run is needed.
            $shouldReprocess = ($dto->content_jp !== null && $dto->content_jp !== $domainArticle->getContentJp()->value)
                || ($dto->title_jp !== null && $dto->title_jp !== $domainArticle->getTitleJp()->value);

            /** @var array{0: DomainArticle, 1: int|null} $outcome */
            $outcome = DB::transaction(function () use ($domainArticle, $dto, $authenticatedUser, $shouldReprocess): array {
                $updatedDomainArticle = $this->applyUpdates($domainArticle, $dto);

                $this->articleRepository->update($updatedDomainArticle);

                if ($dto->hashtags !== null) {
                    $hashtagResult = $this->hashtagService->syncTagsForEntity(
                        $domainArticle->getIdValue(),
                        ObjectTemplateType::ARTICLE,
                        $dto->hashtags,
                        $authenticatedUser->id->value(),
                    );

                    if ($hashtagResult->isFailure()) {
                        throw new \Exception($hashtagResult->getError()->description);
                    }
                }

                $contentVersion = null;

                if ($shouldReprocess) {
                    $contentVersion = $this->articleRepository->bumpContentVersion($domainArticle->getIdValue());

                    $this->processingStates->startOrReset(
                        ProcessingEntityType::Article,
                        $updatedDomainArticle->getUid(),
                        ProcessingTaskType::ArticleContentProcessing,
                        $contentVersion,
                    );
                }

                return [$updatedDomainArticle, $contentVersion];
            });

            [$updatedDomainArticle, $contentVersion] = $outcome;

            if ($contentVersion !== null) {
                ProcessArticleContentJob::dispatch($updatedDomainArticle->getUid()->value(), $contentVersion);
            }

            return Result::success(
                new ArticleUpdateResultDTO(
                    article: $updatedDomainArticle,
                    hashtags: $this->hashtagService->getHashtags(
                        $updatedDomainArticle->getIdValue(),
                        ObjectTemplateType::ARTICLE
                    ),
                    processingState: $this->processingStateReader->currentState($updatedDomainArticle->getUid()->value()),
                )
            );
        } catch (\Exception $e) {
            Log::error('Article update failed', [
                'user_id' => $authenticatedUser->id->value(),
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
            $article->getJlptLevels(), // Recomputed by ProcessArticleContentJob when content changes
            $article->getCreatedAt(),
            now()->toDateTimeImmutable(), // Always update timestamp
        );
    }

    /**
     * Delete article with full cleanup of relationships and engagement data.
     *
     * @param EntityId $articleUuid Article UUID
     *
     * @return Result Success data: null, Failure data: ResultError
     */
    public function deleteArticle(EntityId $articleUuid, AuthenticatedUser $authenticatedUser): Result
    {
        try {
            DB::transaction(function () use ($articleUuid, $authenticatedUser) {
                $article = $this->articleRepository->findByPublicUid($articleUuid);

                if (! $article) {
                    throw new ArticleNotFoundException($articleUuid->value());
                }

                if (! $this->articlePolicy->canDelete($authenticatedUser, $article)) {
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
