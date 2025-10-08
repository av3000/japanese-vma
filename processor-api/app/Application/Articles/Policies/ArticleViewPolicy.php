<?php
namespace App\Application\Articles\Policies;

use App\Infrastructure\Persistence\Models\Article;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Http\User;

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

        if ($user->isAdmin()) {
            // Admins can see everything - no restrictions
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
        // Public articles are viewable by everyone
        if ($article->publicity === PublicityStatus::PUBLIC) {
            return true;
        }

        // Private articles require authentication
        if ($user === null) {
            return false;
        }

        // Admins can view everything, authors can view their own articles
        return $user->isAdmin() || $user->id === $article->user_id;
    }
}
