<?php

namespace App\Application\Articles\Policies;

use App\Application\Users\Services\RoleServiceInterface;
use App\Domain\Articles\Models\Article;

use App\Domain\Shared\Enums\{PublicityStatus};
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\User;

class ArticlePolicy
{
    public function __construct(
        private readonly RoleServiceInterface $roleService
    ) {}

    /**
     * Business rule: Determine what visibility criteria apply to a user
     * Returns domain concepts, not database queries
     */
    public function getVisibilityCriteria(?User $user): array
    {
        if ($user === null) {
            // Anonymous users can only see public articles
            return [
                'publicity' => [PublicityStatus::PUBLIC],
                'user_id' => null
            ];
        }

        // Use RoleService instead of direct hasRole
        if ($this->roleService->isAdmin(new EntityId($user->uuid))) {
            return [
                'publicity' => 'all',
                'user_id' => 'all'
            ];
        }

        // Regular users can see public articles and their own private articles
        return [
            'publicity' => [PublicityStatus::PUBLIC, PublicityStatus::PRIVATE],
            'user_id' => $user->id,
            'access_own_private' => true
        ];
    }

    /**
     * * TODO:
     * As a business logic this method should work with domain objects, not database concerns.
     * But as a HTTP/authorisation policy maybe it does make sense to be in this layer with database concerns?
     * Figure out how to refactor this to domain logic.
     *
     * Determine if user can view an article
     */
    public function canView(?User $user, Article $article): bool
    {
        // Public articles are viewable by everyone
        if ($article->getPublicity() === PublicityStatus::PUBLIC) {
            return true;
        }

        // Anonymous users can't view private articles
        if ($user === null) {
            return false;
        }

        // Admins can view everything
        if ($this->roleService->isAdmin(new EntityId($user->uuid))) {
            return true;
        }

        // Users can view their own private articles
        return $user->id === $article->getAuthorId()->value();
    }

    /**
     * Determine if user can delete an article
     */
    public function canDelete(?User $user, Article $article): bool
    {
        if ($user === null) {
            return false;
        }

        // TODO: This should be allowed via permission groups Admin should inherit proper rights to delete.
        if ($this->roleService->isAdmin(new EntityId($user->uuid))) {
            return true;
        }

        return $user->id === $article->getAuthorId()->value();
    }

    /**
     * Determine if user can update an article.
     * Business rule: Only the owner or admin can update articles.
     */
    public function canUpdate(?User $user, Article $article): bool
    {
        if ($user === null) {
            return false;
        }

        // Admins can update anything
        if ($this->roleService->isAdmin(new EntityId($user->uuid))) {
            return true;
        }

        // Users can update their own articles
        return $user->id === $article->getAuthorId()->value();
    }
}
