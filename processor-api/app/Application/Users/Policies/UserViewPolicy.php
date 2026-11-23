<?php

declare(strict_types=1);

namespace App\Application\Users\Policies;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Users\Models\User as DomainUser;

class UserViewPolicy
{
    /**
     * Determine if the authenticated user can view the profile.
     * Currently allows all authenticated users to view any profile.
     * Private data visibility is handled at Resource serialization level.
     *
     * @param DomainUser $user The user profile being viewed
     */
    public function view(?AuthenticatedUser $authenticatedUser, DomainUser $user): bool
    {
        // TODO: In future could add logic for private profiles, blocked users, etc.
        return true;
    }

    /**
     * Check if authenticated user is viewing their own profile.
     * Used by Resource to determine email visibility.
     *
     * @param EntityId $uuid The user profile being viewed
     */
    public function isOwnProfile(?AuthenticatedUser $authenticatedUser, EntityId $uuid): bool
    {
        if ($authenticatedUser === null) {
            return false;
        }

        return $authenticatedUser->uuid->equals($uuid);
    }
}
