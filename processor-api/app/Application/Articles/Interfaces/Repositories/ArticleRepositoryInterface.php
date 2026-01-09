<?php

namespace App\Application\Articles\Interfaces\Repositories;

use App\Domain\Articles\Models\Article as DomainArticle;
use App\Domain\Articles\Models\Articles;
use App\Domain\Articles\DTOs\{ArticleCriteriaDTO, ArticleIncludeOptionsDTO};
use App\Domain\Shared\ValueObjects\{UserId, EntityId};

interface ArticleRepositoryInterface
{
    /**
     * Create a new article in persistence.
     *
     * @param DomainArticle $article The domain article to create
     * @return DomainArticle The created article with generated ID
     * @throws \Illuminate\Database\QueryException On database constraint violation
     */
    public function create(DomainArticle $article): DomainArticle;

    /**
     * Update an existing article in persistence.
     *
     * @param DomainArticle $article The domain article with updated state
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If article doesn't exist
     */
    public function update(DomainArticle $article): void;

    /**
     * Find article by public UUID with optional selective eager loading.
     *
     *
     * @param EntityId $articleUuid The article's public UUID
     * @param ArticleIncludeOptionsDTO|null $dto Options for eager loading:
     * @return DomainArticle|null The domain article if found, null if not found
     * @throws \Illuminate\Database\QueryException On database failure
     */
    public function findByPublicUid(EntityId $articleUuid, ?ArticleIncludeOptionsDTO $dto = null): ?DomainArticle;

    /**
     * Find articles matching complex criteria with filters, search, sorting, and pagination.
     * @param ArticleCriteriaDTO
     * $criteria Complete filter criteria including:
     * @return Articles
     * Domain collection containing:
     * @throws \Illuminate\Database\QueryException On database failure
     */
    public function findByCriteria(ArticleCriteriaDTO $criteria): Articles;

    /**
     * Delete article by integer ID with proper relationship cleanup.
     * Note: Engagement data (likes, views, comments) should be cleaned up
     * by the service layer before calling this method.
     *
     * @param int $id The article's integer ID (not UUID)
     * @return bool True if deleted successfully
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If article with ID not found
     * @throws \Illuminate\Database\QueryException On database failure
     */
    public function deleteById(int $id): bool;

    /**
     * Find articles by author user ID with limit.
     *
     * Returns most recent articles by a specific user, ordered by creation date.
     * Eager loads user and kanjis relationships.
     *
     * @param UserId $authorId The author's user ID
     * @param int $limit Maximum number of articles to return (default: 10)
     * @return array<array> Array of article arrays (not domain models, raw Eloquent arrays)
     * @throws \Illuminate\Database\QueryException On database failure
     * @todo Potentially not needed, as findByCriteria can be sufficient
     */
    public function findByUserId(UserId $authorId, int $limit = 10): array;

    /**
     * Get integer ID from article UUID.
     *
     * Useful for operations that require the integer ID but only have the public UUID.
     *
     * @param EntityId $entityUuid The article's public UUID
     * @return int|null The article's integer ID, or null if not found
     * @throws \Illuminate\Database\QueryException On database failure
     */
    public function getIdByUuid(EntityId $entityUuid): int|null;

    /**
     * Syncs a list of Kanji IDs to an article.
     * This replaces any existing kanjis attached to the article.
     *
     * @param int $articleId The internal ID of the article.
     * @param int[] $kanjiIds An array of Kanji internal IDs to attach.
     * @return void
     */
    public function syncKanjis(int $articleId, array $kanjiIds): void;
}
