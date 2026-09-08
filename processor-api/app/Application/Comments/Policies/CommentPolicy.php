<?php

namespace App\Application\Comments\Policies;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Comments\Models\Comment;

class CommentPolicy
{
    /**
     * Editing a comment rewrites someone else's words, so it stays owner-only.
     * This deliberately differs from ArticlePolicy::canUpdate, where admins
     * may edit any article: moderation of comments is deletion, not rewriting.
     */
    public function canUpdate(?AuthenticatedUser $authenticatedUser, Comment $comment): bool
    {
        if ($authenticatedUser === null) {
            return false;
        }

        return $comment->isAuthoredBy($authenticatedUser->id);
    }

    public function canDelete(?AuthenticatedUser $authenticatedUser, Comment $comment): bool
    {
        if ($authenticatedUser === null) {
            return false;
        }

        if ($authenticatedUser->isAdmin) {
            return true;
        }

        return $comment->isAuthoredBy($authenticatedUser->id);
    }
}
