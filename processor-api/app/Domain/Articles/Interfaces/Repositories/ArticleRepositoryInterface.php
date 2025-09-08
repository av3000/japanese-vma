<?php
namespace App\Domain\Articles\Repositories;

use App\Domain\Articles\Models\Article;  // Domain model, not persistence model
use App\Domain\Articles\DTOs\ArticleListDTO;
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
    public function save(Article $article, ?string $tags = null): void;

    /**
     * Find article by domain ID and convert to domain model
     * Returns null if not found, throws exception if access denied
     */
    public function findById(ArticleId $id, ?UserId $userId = null): ?Article;

    /**
     * Get paginated articles with domain filtering
     * This method applies business rules about visibility and access
     */
    public function getPaginated(ArticleListDTO $filters, ?User $user = null): LengthAwarePaginator;

    /**
     * Delete article by ID with proper authorization
     * Returns true if deleted, false if not found or unauthorized
     */
    public function deleteById(ArticleId $id, UserId $userId, bool $isAdmin = false): bool;

    /**
     * Find articles by author for user management features
     */
    public function findByAuthor(UserId $authorId, int $limit = 10): array;

    /**
     * Check if article exists and user can access it
     * Useful for authorization checks before operations
     */
    public function canUserAccess(ArticleId $id, ?UserId $userId = null): bool;
}
