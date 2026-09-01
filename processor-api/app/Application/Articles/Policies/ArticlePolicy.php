<?php

namespace App\Application\Articles\Policies;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Articles\Models\Article;
use App\Domain\Shared\Enums\PublicityStatus;

class ArticlePolicy
{
    /**
     * Business rule: Determine what visibility criteria apply to a user
     * Returns domain concepts, not database queries
     */
    public function getVisibilityCriteria(?AuthenticatedUser $authenticatedUser): array
    {
        if ($authenticatedUser === null) {
            // Anonymous users can only see public articles
            return [
                'publicity' => [PublicityStatus::PUBLIC],
                'user_id' => null,
            ];
        }

        if ($authenticatedUser->isAdmin) {
            return [
                'publicity' => 'all',
                'user_id' => 'all',
            ];
        }

        // Regular users can see public articles and their own private articles
        return [
            'publicity' => [PublicityStatus::PUBLIC, PublicityStatus::PRIVATE],
            'user_id' => $authenticatedUser->id->value(),
            'access_own_private' => true,
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
    public function canView(?AuthenticatedUser $authenticatedUser, Article $article): bool
    {
        // Public articles are viewable by everyone
        if ($article->getPublicity() === PublicityStatus::PUBLIC) {
            return true;
        }

        // Anonymous users can't view private articles
        if ($authenticatedUser === null) {
            return false;
        }

        // Admins can view everything
        if ($authenticatedUser->isAdmin) {
            return true;
        }

        // Users can view their own private articles
        return $authenticatedUser->id->equals($article->getAuthorId());
    }

    /**
     * Determine if user can delete an article
     */
    public function canDelete(?AuthenticatedUser $authenticatedUser, Article $article): bool
    {
        if ($authenticatedUser === null) {
            return false;
        }

        // TODO: This should be allowed via permission groups Admin should inherit proper rights to delete.
        if ($authenticatedUser->isAdmin) {
            return true;
        }

        return $authenticatedUser->id->equals($article->getAuthorId());
    }

    /**
     * Determine if user can update an article.
     * Business rule: Only the owner or admin can update articles.
     */
    public function canUpdate(?AuthenticatedUser $authenticatedUser, Article $article): bool
    {
        if ($authenticatedUser === null) {
            return false;
        }

        // Admins can update anything
        if ($authenticatedUser->isAdmin) {
            return true;
        }

        // Users can update their own articles
        return $authenticatedUser->id->equals($article->getAuthorId());
    }
}
