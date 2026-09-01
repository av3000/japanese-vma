<?php

namespace App\Application\Catalogues\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Catalogues\DTOs\CatalogueCreateDTO;
use App\Domain\Catalogues\DTOs\CatalogueDetailDTO;
use App\Domain\Catalogues\DTOs\CatalogueListDTO;
use App\Domain\Catalogues\DTOs\CatalogueListResultDTO;
use App\Domain\Catalogues\DTOs\CataloguePickerResultDTO;
use App\Domain\Catalogues\DTOs\CatalogueUpdateDTO;
use App\Domain\Catalogues\DTOs\CatalogueUpdateResultDTO;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Viewer;
use App\Shared\Results\Result;

interface CatalogueServiceInterface
{
    public function createCatalogue(CatalogueCreateDTO $dto, AuthenticatedUser $authenticatedUser): Result;

    public function getCatalogueList(CatalogueListDTO $dto, ?AuthenticatedUser $authenticatedUser = null): CatalogueListResultDTO;

    public function getCataloguesForItem(int $itemId, array $types, ?string $search, AuthenticatedUser $authenticatedUser): CataloguePickerResultDTO;

    /**
     * @return Result<CatalogueDetailDTO>
     */
    public function getCatalogueDetail(EntityId $uuid, Viewer $viewer, ?AuthenticatedUser $authenticatedUser = null): Result;

    public function getIdByUuid(EntityId $uuid): ?int;

    public function addItemToCatalogue(EntityId $uuid, int $itemId, AuthenticatedUser $authenticatedUser): Result;

    public function removeItemFromCatalogue(EntityId $uuid, int $itemId, AuthenticatedUser $authenticatedUser): Result;

    /**
     * @return Result<CatalogueUpdateResultDTO>
     */
    public function updateCatalogue(EntityId $uuid, CatalogueUpdateDTO $dto, AuthenticatedUser $authenticatedUser): Result;

    public function deleteCatalogue(EntityId $uuid, AuthenticatedUser $authenticatedUser): Result;
}
