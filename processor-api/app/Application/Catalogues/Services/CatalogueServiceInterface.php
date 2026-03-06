<?php

namespace App\Application\Catalogues\Services;

use App\Domain\Catalogues\DTOs\CatalogueListDTO;
use App\Domain\Catalogues\Models\Catalogues;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;
use App\Infrastructure\Persistence\Models\User;

interface CatalogueServiceInterface
{
    public function getCatalogueList(CatalogueListDTO $dto, ?User $user = null): Catalogues;

    public function getCatalogue(EntityId $uuid, ?User $user = null): Result;
}
