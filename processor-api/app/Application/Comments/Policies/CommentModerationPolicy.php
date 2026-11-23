<?php

declare(strict_types=1);

namespace App\Application\Comments\Policies;

use App\Application\Auth\DTOs\AuthenticatedUser;

/**
 * Rules for staff acting on other people's comments. Only CommentModerationService reads this;
 * CommentPolicy stays about authors and readers. Moderating a comment means removing it, never
 * rewriting it.
 */
class CommentModerationPolicy
{
    public function canModerate(?AuthenticatedUser $authenticatedUser): bool
    {
        return $authenticatedUser?->isAdmin === true;
    }
}
