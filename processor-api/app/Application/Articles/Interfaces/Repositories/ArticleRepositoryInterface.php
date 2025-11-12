<?php
namespace App\Application\Articles\Interfaces\Repositories;

use App\Domain\Articles\Models\Article as DomainArticle;
use App\Domain\Articles\Models\Articles;
use App\Domain\Articles\DTOs\{ArticleCriteriaDTO, ArticleIncludeOptionsDTO};
use App\Domain\Shared\ValueObjects\{UserId, EntityId};

interface ArticleRepositoryInterface
{
    /**
     * Save a domain article to persistence (create or update).
     *
     * Converts domain model to persistence entity, saves to database,
     * reloads with user relationship, and converts back to domain model.
     *
     * @param DomainArticle $article The domain article to save
     * @return DomainArticle|null The saved article with generated ID, or null on failure
     * @throws \Illuminate\Database\QueryException On database constraint violation or connection failure
     */
    public function save(DomainArticle $article): ?DomainArticle;

    /**
     * Find article by public UUID with optional eager loading.
     *
     * Allows selective loading of relationships (user, kanjis, words) to avoid N+1 queries
     * when needed and reduce query overhead when not needed.
     *
     * @param EntityId $articleUuid The article's public UUID
     * @param ArticleIncludeOptionsDTO|null $dto Options for eager loading relationships (user, kanjis, words)
     * @return DomainArticle|null The domain article if found, null otherwise
     * @throws \Illuminate\Database\QueryException On database failure
     */
    public function findByPublicUid(EntityId $articleUuid, ?ArticleIncludeOptionsDTO $dto = null): ?DomainArticle;

    /**
     * Find articles matching complex criteria with filters, search, sorting, and pagination.
     *
     * Handles permission-based filtering, content search, sorting, and pagination
     * in a single optimized query. Converts persistence models to domain collection.
     *
     * @param ArticleCriteriaDTO $dto Filter criteria including:
     *                                - visibility rules (public/private based on user permissions)
     *                                - search term (title_jp, title_en)
     *                                - category filter
     *                                - sorting (field + direction)
     *                                - pagination (page, per_page)
     * @return Articles Domain collection with paginated articles and metadata
     * @throws \Illuminate\Database\QueryException On database failure
     */
    public function findByCriteria(ArticleCriteriaDTO $dto): Articles;

    /**
     * Delete article by integer ID with relationship cleanup.
     *
     * Detaches all many-to-many relationships (kanjis, words) before deletion
     * to maintain referential integrity.
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
}
