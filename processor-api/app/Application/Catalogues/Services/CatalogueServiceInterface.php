<?php

namespace App\Application\Catalogues\Services;

use App\Domain\Catalogues\DTOs\CatalogueCreateDTO;
use App\Domain\Catalogues\DTOs\CatalogueListDTO;
use App\Domain\Catalogues\DTOs\CatalogueUpdateDTO;
use App\Domain\Catalogues\Models\Catalogues;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;
use App\Infrastructure\Persistence\Models\User;

interface CatalogueServiceInterface
{
    public function createCatalogue(CatalogueCreateDTO $dto, User $user): Result;

    public function getCatalogueList(CatalogueListDTO $dto, ?User $user = null): Catalogues;

    public function getCatalogue(EntityId $uuid, ?User $user = null): Result;

    public function addItemToCatalogue(EntityId $uuid, int $itemId, User $user): Result;

    public function updateCatalogue(EntityId $uuid, CatalogueUpdateDTO $dto, User $user): Result;

    public function deleteCatalogue(EntityId $uuid, User $user): Result;
}
