<?php
namespace App\Application\Articles\Policies;

use App\Domain\Articles\Models\Article;

use App\Domain\Shared\Enums\{PublicityStatus, UserRole};
use App\Http\User;

// TODO: rename to ArticlePolicy
class ArticleViewPolicy
{
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

        if ($user->hasRole(UserRole::ADMIN->value)) {
            return [
                'publicity' => 'all',
                'user_id' => 'all'
            ];
        }

        // Regular users can see public articles and their own private articles
        return [
            'publicity' => [PublicityStatus::PUBLIC, PublicityStatus::PRIVATE],
            'user_id' => $user->id,
             // TODO: rules should be defined as const or enums or some other form than raw string array.
            'access_own_private' => true
        ];
    }

    /**
     * TODO:
     * As a business logic this method should work with domain objects, not database concerns.
     * But as a HTTP/authorisation policy maybe it does make sense to be in this layer with database concerns?
     * Figure out how to refactor this to domain logic.
     */
    public function canView(?User $user, Article $article): bool
    {
        if ($article->getPublicity() === PublicityStatus::PUBLIC) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        return $user->hasRole(UserRole::ADMIN->value) || $user->id === $article->getAuthorId()->value();
    }

    // TODO: should use Article domain instead of persistence article when refactored
    public function canDelete(?User $user, Article $article): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->id === $article->getIdValue()) {
            return true;
        }

        return $user->hasRole(UserRole::ADMIN->value);
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

        if ($user->id === $article->getAuthorId()->value()) {
            return true;
        }

        return $user->hasRole(UserRole::ADMIN->value);
    }
}
