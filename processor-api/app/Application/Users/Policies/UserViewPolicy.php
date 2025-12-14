<?php

declare(strict_types=1);

namespace App\Application\Users\Policies;

use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Users\Models\User as DomainUser;
// use App\Http\User as AuthUser; // TODO: use new model when refactoring will be done for dependent entities.
use App\Infrastructure\Persistence\Models\User as AuthUser;

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
        // TODO: In future could add logic for private profiles, blocked users, etc.
        return true;
    }

    /**
     * Check if authenticated user is viewing their own profile.
     * Used by Resource to determine email visibility.
     *
     * @param DomainUser|null $domainUser The authenticated domain user
     * @param EntityId $uuid The user profile being viewed
     * @return bool
     */
    public function isOwnProfile(?DomainUser $domainUser, EntityId $uuid): bool
    {
        if (!$domainUser) {
            return false;
        }

        return $domainUser->getUuid()->value() === $uuid->value();
    }
}
