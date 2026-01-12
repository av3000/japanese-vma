<?php

namespace App\Application\Articles\Services;

use App\Domain\Articles\DTOs\{ArticleCreateDTO, ArticleIncludeOptionsDTO, ArticleUpdateDTO, ArticleListDTO};
use App\Domain\Articles\Models\{Articles};
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface ArticleServiceInterface
{
    /**
     * Create article with hashtags in single transaction.
     *
     * @param ArticleCreateDTO $dto Article data including title, content, tags
     * @param User $user The authenticated user creating the article
     * @return Result Success data: Article, Failure data: Error (creationFailed, invalidTag)
     */
    public function createArticle(ArticleCreateDTO $dto, User $user): Result;

    public function getArticleIdByUuid(EntityId $uuid): int;

    /**
     * Get single article with optional relationships and permission check.
     * Tracks view if user has access.
     *
     * @param EntityId $articleUid Article's public UUID
     * @param ArticleIncludeOptionsDTO $dto Options for eager loading (user, kanjis, words)
     * @param User|null $user Current user for permission check
     * @return Result Success data: Article, Failure data: Error (notFound, accessDenied)
     */
    public function getArticle(EntityId $articleUid, ArticleIncludeOptionsDTO $dto, ?User $user = null): Result;

    /**
     * Get paginated list of articles with filters and permission-based visibility.
     *
     * @param ArticleListDTO $dto Filters: search, category, sort, pagination
     * @param User|null $user Current user for visibility rules
     * @return Articles Domain collection with paginated results
     */
    public function getArticlesList(ArticleListDTO $dto, ?User $user = null): Articles;

    /**
     * Update article with optional hashtag and content reprocessing.
     *
     * @param int $id Article integer ID
     * @param ArticleUpdateDTO $dto Fields to update
     * @param int $userId User ID for authorization
     * @return Result Success data: Article, Failure data: Error (notFound, unauthorized)
     * @todo Refactor to use EntityId and return domain model instead of persistence model
     */
    public function updateArticle(string $uid, ArticleUpdateDTO $dto, User $user): Result;

    /**
     * Delete article with full cleanup (relationships, engagement, hashtags).
     *
     * @param EntityId $articleUuid Article's public UUID
     * @param User $user User requesting deletion (for authorization)
     * @return Result Success data: null (void), Failure data: Error (notFound, accessDenied)
     */
    public function deleteArticle(EntityId $articleUuid, User $user): Result;

    /**
     * Get paginated kanjis for an article.
     *
     * @param int $articleId Article integer ID
     * @param int|null $page Page number
     * @param int|null $perPage Items per page
     * @return LengthAwarePaginator Eloquent paginator with kanji models
     * @todo Return domain models instead of Eloquent models
     */
    public function getArticleKanjis(int $articleId, ?int $page = null, ?int $perPage = null): LengthAwarePaginator;

    /**
     * Get paginated words for an article.
     *
     * @param int $articleId Article integer ID
     * @param int|null $page Page number
     * @param int|null $perPage Items per page
     * @return LengthAwarePaginator Eloquent paginator with word models
     * @todo Return domain models instead of Eloquent models
     */
    public function getArticleWords(int $articleId, ?int $page = null, ?int $perPage = null): LengthAwarePaginator;
}
