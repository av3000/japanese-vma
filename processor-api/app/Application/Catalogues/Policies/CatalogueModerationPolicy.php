<?php

declare(strict_types=1);

namespace App\Application\Catalogues\Policies;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\Services\TemplateTypeClassifier;

/**
 * Rules for staff acting on other people's catalogues. Only CatalogueModerationService reads
 * this; CataloguePolicy stays about owners and viewers.
 */
class CatalogueModerationPolicy
{
    public function __construct(
        private readonly TemplateTypeClassifier $templateTypeClassifier,
    ) {
    }

    public function canModerate(?AuthenticatedUser $authenticatedUser): bool
    {
        return $authenticatedUser?->isAdmin === true;
    }

    /**
     * Known lists hold a learner's own progress rather than content anyone else sees, so
     * moderation never removes them.
     */
    public function canRemove(?AuthenticatedUser $authenticatedUser, Catalogue $catalogue): bool
    {
        return $this->canRemoveType($authenticatedUser, $catalogue->getType());
    }

    /**
     * The same rule from the catalogue's type alone, for callers that only hold the type.
     */
    public function canRemoveType(?AuthenticatedUser $authenticatedUser, SavedListType $type): bool
    {
        return $this->canModerate($authenticatedUser)
            && ! $this->templateTypeClassifier->isKnownType($type);
    }
}
