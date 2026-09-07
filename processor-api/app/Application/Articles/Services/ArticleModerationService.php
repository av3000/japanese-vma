<?php

namespace App\Application\Articles\Services;

use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Application\Articles\Policies\ArticlePolicy;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Engagement\Services\HashtagServiceInterface;
use App\Domain\Articles\DTOs\ArticleModerationItemDTO;
use App\Domain\Articles\DTOs\ArticleModerationListResultDTO;
use App\Domain\Articles\DTOs\ArticleStatusResultDTO;
use App\Domain\Articles\Errors\ArticleErrors;
use App\Domain\Articles\Models\Article;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Shared\Results\Result;
use Illuminate\Support\Facades\Log;
use Throwable;

class ArticleModerationService implements ArticleModerationServiceInterface
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articleRepository,
        private readonly ArticlePolicy $articlePolicy,
        private readonly HashtagServiceInterface $hashtagService,
    ) {
    }

    public function getPendingArticles(Pagination $pagination, AuthenticatedUser $authenticatedUser): Result
    {
        if (! $this->articlePolicy->canModerate($authenticatedUser)) {
            return Result::failure(ArticleErrors::accessDenied('moderation'));
        }

        try {
            $articles = $this->articleRepository->findModerationQueue($pagination);
            $items = $articles->getItems();
            $articleIds = array_map(
                static fn (Article $article): int => $article->getIdValue(),
                $items,
            );
            $hashtagsByArticleId = $articleIds === []
                ? []
                : $this->hashtagService->getBatchHashtags($articleIds, ObjectTemplateType::ARTICLE);
            $paginator = $articles->getPaginator();

            return Result::success(new ArticleModerationListResultDTO(
                items: array_map(
                    static fn (Article $article): ArticleModerationItemDTO => new ArticleModerationItemDTO(
                        article: $article,
                        hashtags: $hashtagsByArticleId[$article->getIdValue()] ?? [],
                    ),
                    $items,
                ),
                pagination: [
                    'page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'has_more' => $paginator->hasMorePages(),
                ],
            ));
        } catch (Throwable $exception) {
            Log::error('Article moderation queue fetch failed', [
                'user_id' => $authenticatedUser->id->value(),
                'error' => $exception->getMessage(),
            ]);

            return Result::failure(ArticleErrors::updateFailed($exception->getMessage()));
        }
    }

    public function updateStatus(EntityId $articleUuid, ArticleStatus $status, AuthenticatedUser $authenticatedUser): Result
    {
        if (! $this->articlePolicy->canModerate($authenticatedUser)) {
            return Result::failure(ArticleErrors::accessDenied($articleUuid->value()));
        }

        try {
            $persistedStatus = $this->articleRepository->updateStatus($articleUuid, $status);

            if ($persistedStatus === null) {
                return Result::failure(ArticleErrors::notFound($articleUuid->value()));
            }

            return Result::success(new ArticleStatusResultDTO($articleUuid, $persistedStatus));
        } catch (Throwable $exception) {
            Log::error('Article moderation status update failed', [
                'user_id' => $authenticatedUser->id->value(),
                'article_uuid' => $articleUuid->value(),
                'error' => $exception->getMessage(),
            ]);

            return Result::failure(ArticleErrors::updateFailed($exception->getMessage()));
        }
    }
}
