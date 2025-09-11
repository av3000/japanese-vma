<?php
namespace App\Domain\Articles\Policies;

use App\Infrastructure\Persistence\Models\Article;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Articles\Interfaces\Policies\ArticleViewPolicyInterface;
use App\Http\User;

class ArticleViewPolicy implements ArticleViewPolicyInterface
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
            'access_own_private' => true
        ];
    }

    /**
     * Business rule: Can specific user view specific article
     * This method works with domain objects, not database concerns
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
