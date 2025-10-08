<?php
namespace App\Application\Articles\Interfaces\Repositories;

use App\Infrastructure\Persistence\Models\Article;
use App\Domain\Articles\Models\Article as DomainArticle;
use App\Domain\Articles\Models\Articles;
use App\Domain\Articles\DTOs\ArticleCriteriaDTO;
use App\Domain\Articles\ValueObjects\ArticleId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Http\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface ArticleRepositoryInterface
{
    /**
     * Save a domain article (create or update)
     * This method handles the complexity of converting domain model to persistence
     */
    public function save(DomainArticle $article): DomainArticle;

    /**
     * Save article along with associated kanji IDs
     * Ensure kanji relationships are properly managed
     */
    public function saveWithKanjis(DomainArticle $article, array $kanjiIds): DomainArticle;

    /**
     * Find article by domain ID and convert to domain model
     * Returns null if not found, throws exception if access denied
     */
    public function findById(ArticleId $id): ?DomainArticle;

    /**
     * Find articles by unique domain ID (EntityId) and convert to domain model
     * Returns null if not found, throws exception if access denied
     */
    public function findByCriteria(ArticleCriteriaDTO $dto): Articles;

    /**
     * Delete article by ID with proper authorization
     * Returns true if deleted, false if not found or unauthorized
     */
    public function deleteById(ArticleId $id): bool;

    /**
     * Find articles by author ID
     * Returns paginated array of articles or empty array if none found
     */
    // TODO: potentially not needed, as getPaginated can be sufficient
    public function findByUserId(UserId $authorId, int $limit = 10): array;
}
