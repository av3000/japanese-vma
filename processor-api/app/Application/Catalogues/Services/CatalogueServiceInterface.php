<?php

namespace App\Application\Catalogues\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Catalogues\DTOs\CatalogueCreateDTO;
use App\Domain\Catalogues\DTOs\CatalogueDetailDTO;
use App\Domain\Catalogues\DTOs\CatalogueLegacyIdentityDTO;
use App\Domain\Catalogues\DTOs\CatalogueListDTO;
use App\Domain\Catalogues\DTOs\CatalogueListResultDTO;
use App\Domain\Catalogues\DTOs\CataloguePickerResultDTO;
use App\Domain\Catalogues\DTOs\CatalogueUpdateDTO;
use App\Domain\Catalogues\DTOs\CatalogueUpdateResultDTO;
use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Viewer;
use App\Shared\Results\Result;

interface CatalogueServiceInterface
{
    public function createCatalogue(CatalogueCreateDTO $dto, AuthenticatedUser $authenticatedUser, Viewer $viewer): Result;

    public function getCatalogueList(CatalogueListDTO $dto, ?AuthenticatedUser $authenticatedUser = null): CatalogueListResultDTO;

    public function getCataloguesForItem(int $itemId, array $types, ?string $search, AuthenticatedUser $authenticatedUser): CataloguePickerResultDTO;

    /**
     * @return Result<CatalogueDetailDTO>
     */
    public function getCatalogueDetail(EntityId $uuid, Viewer $viewer, ?AuthenticatedUser $authenticatedUser = null): Result;

    public function getIdByUuid(EntityId $uuid): ?int;

    /**
     * Resolve a catalogue the viewer may see, and nothing more: no view is recorded and no
     * items, stats or hashtags are assembled. For modules that need the catalogue as an
     * input (the Study deck builder) rather than as a page.
     *
     * @return Result Success data: Catalogue. Catalogues.NotFound or Catalogues.AccessDenied on failure.
     */
    public function getViewableCatalogue(EntityId $uuid, ?AuthenticatedUser $authenticatedUser = null): Result;

    /**
     * The ids of the dictionary items in a catalogue. No policy check: the caller resolved
     * the catalogue through getViewableCatalogue() first, or holds a reference it made then.
     *
     * @return Result Success data: int[].
     */
    public function getCatalogueItemIds(int $catalogueId): Result;

    /**
     * Whether a catalogue holds an item. Same caller contract as getCatalogueItemIds().
     *
     * @return Result Success data: bool.
     */
    public function catalogueContainsItem(int $catalogueId, int $itemId): Result;

    /**
     * Resolve a legacy numeric catalogue id to its canonical UUID identity.
     *
     * Visibility-safe and side-effect free: no view is recorded and no detail
     * payload is assembled.
     *
     * @return Result<CatalogueLegacyIdentityDTO>
     */
    public function resolveLegacyIdentity(int $legacyId, ?AuthenticatedUser $authenticatedUser = null): Result;

    public function addItemToCatalogue(EntityId $uuid, int $itemId, AuthenticatedUser $authenticatedUser): Result;

    public function removeItemFromCatalogue(EntityId $uuid, int $itemId, AuthenticatedUser $authenticatedUser): Result;

    /**
     * @return Result<CatalogueUpdateResultDTO>
     */
    public function updateCatalogue(EntityId $uuid, CatalogueUpdateDTO $dto, AuthenticatedUser $authenticatedUser): Result;

    public function deleteCatalogue(EntityId $uuid, AuthenticatedUser $authenticatedUser): Result;
}
