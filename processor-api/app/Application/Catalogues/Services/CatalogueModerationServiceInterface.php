<?php

declare(strict_types=1);

namespace App\Application\Catalogues\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;

/**
 * Staff acting on other people's catalogues, from the admin panel only. Owners change and
 * delete their own catalogues through CatalogueServiceInterface. Every change is recorded in
 * the activity log.
 */
interface CatalogueModerationServiceInterface
{
    /**
     * Hiding a list is moderation; rewriting its title or items is not, so publicity is the one
     * field staff change. Setting the publicity a catalogue already has records nothing.
     *
     * @return Result<PublicityStatus>
     */
    public function setPublicity(EntityId $uuid, PublicityStatus $publicity, AuthenticatedUser $authenticatedUser): Result;

    /**
     * Removes a custom list with everything attached to it. Known lists are refused.
     *
     * @return Result<null>
     */
    public function remove(EntityId $uuid, AuthenticatedUser $authenticatedUser): Result;
}
