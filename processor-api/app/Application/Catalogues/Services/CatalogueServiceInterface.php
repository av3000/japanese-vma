<?php

namespace App\Application\Catalogues\Services;

use App\Domain\Catalogues\DTOs\CatalogueCreateDTO;
use App\Domain\Catalogues\DTOs\CatalogueDetailDTO;
use App\Domain\Catalogues\DTOs\CatalogueListDTO;
use App\Domain\Catalogues\DTOs\CatalogueListResultDTO;
use App\Domain\Catalogues\DTOs\CataloguePickerResultDTO;
use App\Domain\Catalogues\DTOs\CatalogueUpdateDTO;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\User;
use App\Shared\Results\Result;

interface CatalogueServiceInterface
{
    public function createCatalogue(CatalogueCreateDTO $dto, User $user): Result;

    public function getCatalogueList(CatalogueListDTO $dto, ?User $user = null): CatalogueListResultDTO;

    public function getCataloguesForItem(int $itemId, array $types, ?string $search, User $user): CataloguePickerResultDTO;

    /**
     * @return Result<CatalogueDetailDTO>
     */
    public function getCatalogueDetail(EntityId $uuid, ?User $user = null): Result;

    public function getIdByUuid(EntityId $uuid): ?int;

    public function addItemToCatalogue(EntityId $uuid, int $itemId, User $user): Result;

    public function removeItemFromCatalogue(EntityId $uuid, int $itemId, User $user): Result;

    public function updateCatalogue(EntityId $uuid, CatalogueUpdateDTO $dto, User $user): Result;

    public function deleteCatalogue(EntityId $uuid, User $user): Result;
}
