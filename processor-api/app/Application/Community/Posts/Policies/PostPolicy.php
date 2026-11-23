<?php

declare(strict_types=1);

namespace App\Application\Community\Posts\Policies;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Community\Posts\Models\Post;

/**
 * Post moderation rules, split three ways on purpose.
 *
 * Legacy PostController collapsed these: update was owner-only, delete was
 * `owner || admin` written with a precedence bug, and toggleLock had no check
 * at all on the non-admin route. v1 keeps update owner-only - an admin moderates
 * a Post by locking or removing it, not by rewriting someone else's words.
 */
final class PostPolicy
{
    public function canUpdate(?AuthenticatedUser $authenticatedUser, Post $post): bool
    {
        if ($authenticatedUser === null) {
            return false;
        }

        return $this->isOwner($authenticatedUser, $post);
    }

    public function canDelete(?AuthenticatedUser $authenticatedUser, Post $post): bool
    {
        if ($authenticatedUser === null) {
            return false;
        }

        return $authenticatedUser->isAdmin || $this->isOwner($authenticatedUser, $post);
    }

    public function canLock(?AuthenticatedUser $authenticatedUser): bool
    {
        return $authenticatedUser?->isAdmin === true;
    }

    private function isOwner(AuthenticatedUser $authenticatedUser, Post $post): bool
    {
        return $authenticatedUser->id->value() === $post->getAuthorId();
    }
}
