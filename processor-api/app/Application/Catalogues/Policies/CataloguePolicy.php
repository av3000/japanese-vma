<?php

namespace App\Application\Catalogues\Policies;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Services\TemplateTypeClassifier;

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

    /**
     * Owners may delete any of their catalogues. A moderator may remove someone else's custom list
     * but never their Known lists, which hold a learner's personal progress rather than content
     * anyone else sees.
     */
    public function canDelete(?AuthenticatedUser $authenticatedUser, Catalogue $catalogue): bool
    {
        if ($authenticatedUser === null) {
            return false;
        }

        if ($authenticatedUser->id->equals($catalogue->getOwnerId())) {
            return true;
        }

        return $this->canModerate($authenticatedUser)
            && ! (new TemplateTypeClassifier())->isKnownType($catalogue->getType());
    }

    /**
     * Publicity is the one field a moderator changes on someone else's catalogue: hiding a list
     * is moderation, rewriting its title or items is not.
     */
    public function canChangePublicity(?AuthenticatedUser $authenticatedUser, Catalogue $catalogue): bool
    {
        if ($authenticatedUser === null) {
            return false;
        }

        return $authenticatedUser->id->equals($catalogue->getOwnerId()) || $this->canModerate($authenticatedUser);
    }

    public function canModerate(?AuthenticatedUser $authenticatedUser): bool
    {
        return $authenticatedUser?->isAdmin === true;
    }
}
