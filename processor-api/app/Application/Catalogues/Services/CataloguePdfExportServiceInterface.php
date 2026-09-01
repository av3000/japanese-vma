<?php

namespace App\Application\Catalogues\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;

interface CataloguePdfExportServiceInterface
{
    public function exportKanjis(EntityId $catalogueUuid, AuthenticatedUser $authenticatedUser): Result;

    public function exportWords(EntityId $catalogueUuid, AuthenticatedUser $authenticatedUser): Result;
}
