<?php

namespace App\Application\Catalogues\Services;

use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\User;
use App\Shared\Results\Result;

interface CataloguePdfExportServiceInterface
{
    public function exportKanjis(EntityId $catalogueUuid, User $viewer): Result;

    public function exportWords(EntityId $catalogueUuid, User $viewer): Result;
}
