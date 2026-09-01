<?php

namespace App\Application\Catalogues\Policies;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\Shared\Enums\PublicityStatus;

class CataloguePolicy
{
    public function canView(?AuthenticatedUser $authenticatedUser, Catalogue $catalogue): bool
    {
        if ($catalogue->getPublicity() === PublicityStatus::PUBLIC) {
            return true;
        }

        if ($authenticatedUser === null) {
            return false;
        }

        if ($authenticatedUser->isAdmin) {
            return true;
        }

        return $authenticatedUser->id->equals($catalogue->getOwnerId());
    }

    public function canIndexPrivateCatalogues(?AuthenticatedUser $authenticatedUser, ?string $ownerUid): bool
    {
        if ($authenticatedUser === null || $ownerUid === null) {
            return false;
        }

        if ($authenticatedUser->isAdmin) {
            return true;
        }

        return $authenticatedUser->uuid->value() === $ownerUid;
    }

    public function canUpdate(?AuthenticatedUser $authenticatedUser, Catalogue $catalogue): bool
    {
        if ($authenticatedUser === null) {
            return false;
        }

        return $authenticatedUser->id->equals($catalogue->getOwnerId());
    }

    public function canDelete(?AuthenticatedUser $authenticatedUser, Catalogue $catalogue): bool
    {
        if ($authenticatedUser === null) {
            return false;
        }

        return $authenticatedUser->id->equals($catalogue->getOwnerId());
    }
}
