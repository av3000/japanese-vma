<?php

namespace App\Application\Articles\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Articles\DTOs\ArticleCreateDTO;
use App\Domain\Articles\DTOs\ArticleDetailResultDTO;
use App\Domain\Articles\DTOs\ArticleIncludeOptionsDTO;
use App\Domain\Articles\DTOs\ArticleListDTO;
use App\Domain\Articles\DTOs\ArticleListResultDTO;
use App\Domain\Articles\DTOs\ArticleUpdateDTO;
use App\Domain\Articles\DTOs\ArticleUpdateResultDTO;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Viewer;
use App\Shared\Results\Result;
use Illuminate\Pagination\LengthAwarePaginator;

interface ArticleServiceInterface
{
    /**
     * Create article with hashtags in single transaction.
     *
     * @param ArticleCreateDTO $dto Article data including title, content, tags
     *
     * @return Result Success data: DomainArticle, Failure data: ResultError
     */
    public function createArticle(ArticleCreateDTO $dto, AuthenticatedUser $authenticatedUser): Result;

    public function getArticleIdByUuid(EntityId $uuid): ?int;

    /**
     * Get single article with optional relationships and permission check.
     * Tracks view if user has access.
     *
     * @param EntityId $articleUid Article's public UUID
     * @param ArticleIncludeOptionsDTO $dto Options for eager loading (user, kanjis, words)
     *
     * @return Result Success data: ArticleDetailResultDTO, Failure data: ResultError
     */
    public function getArticle(EntityId $articleUid, ArticleIncludeOptionsDTO $dto, Viewer $viewer, ?AuthenticatedUser $authenticatedUser = null): Result;

    /**
     * Get paginated list of articles with filters and permission-based visibility.
     *
     * @param ArticleListDTO $dto Filters: search, category, sort, pagination
     *
     * @return ArticleListResultDTO Shaped article list with pagination metadata
     */
    public function getArticlesList(ArticleListDTO $dto, ?AuthenticatedUser $authenticatedUser = null): ArticleListResultDTO;

    /**
     * Update article with optional hashtag and content reprocessing.
     *
     * @param string $uid Article public UUID
     * @param ArticleUpdateDTO $dto Fields to update
     *
     * @return Result Success data: ArticleUpdateResultDTO, Failure data: ResultError
     */
    public function updateArticle(string $uid, ArticleUpdateDTO $dto, AuthenticatedUser $authenticatedUser): Result;

    /**
     * Delete article with full cleanup (relationships, engagement, hashtags).
     *
     * @param EntityId $articleUuid Article's public UUID
     *
     * @return Result Success data: null (void), Failure data: ResultError (notFound, accessDenied)
     */
    public function deleteArticle(EntityId $articleUuid, AuthenticatedUser $authenticatedUser): Result;

    /**
     * Get paginated kanjis for an article.
     *
     * @param int $articleId Article integer ID
     * @param int|null $page Page number
     * @param int|null $perPage Items per page
     *
     * @return LengthAwarePaginator Eloquent paginator with kanji models
     *
     * @todo Return domain models instead of Eloquent models
     */
    public function getArticleKanjis(int $articleId, ?int $page = null, ?int $perPage = null): LengthAwarePaginator;

    /**
     * Get paginated words for an article with typed failure handling.
     *
     * @param int $articleId Article integer ID
     * @param int|null $page Page number
     * @param int|null $perPage Items per page
     *
     * @return Result Success data: LengthAwarePaginator, Failure data: ResultError
     */
    public function getArticleWordsResult(int $articleId, ?int $page = null, ?int $perPage = null): Result;

    /**
     * Get paginated words for an article.
     *
     * @param int $articleId Article integer ID
     * @param int|null $page Page number
     * @param int|null $perPage Items per page
     *
     * @return LengthAwarePaginator Paginator with domain word models
     */
    public function getArticleWords(int $articleId, ?int $page = null, ?int $perPage = null): LengthAwarePaginator;
}
