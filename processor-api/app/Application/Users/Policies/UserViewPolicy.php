<?php

declare(strict_types=1);

namespace App\Application\Users\Policies;

use App\Domain\Users\Models\User as DomainUser;
use App\Http\User as AuthUser; // TODO: use new model when refactoring will be done for dependent entities.

class UserViewPolicy
{
    /**
     * Determine if the authenticated user can view the profile.
     * Currently allows all authenticated users to view any profile.
     * Private data visibility is handled at Resource serialization level.
     *
     * @param AuthUser|null $authUser The authenticated user
     * @param DomainUser $user The user profile being viewed
     * @return bool
     */
    public function view(?AuthUser $authUser, DomainUser $user): bool
    {
        // Future: Add logic for private profiles, blocked users, etc.
        return true;
    }

    /**
     * Check if authenticated user is viewing their own profile.
     * Used by Resource to determine email visibility.
     *
     * @param AuthUser|null $authUser The authenticated user
     * @param DomainUser $user The user profile being viewed
     * @return bool
     */
    public function isOwnProfile(?AuthUser $authUser, DomainUser $user): bool
    {
        if (!$authUser) {
            return false;
        }

        return $authUser->id === $user->getId()->value();
    }
}
