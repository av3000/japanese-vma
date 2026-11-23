<?php

declare(strict_types=1);

namespace App\Application\Community\Posts\Policies;

use App\Application\Auth\DTOs\AuthenticatedUser;

/**
 * Rules for staff acting on other people's posts. Only PostModerationService reads this;
 * PostPolicy is about authors and readers.
 */
class PostModerationPolicy
{
    public function canModerate(?AuthenticatedUser $authenticatedUser): bool
    {
        return $authenticatedUser?->isAdmin === true;
    }
}
