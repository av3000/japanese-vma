<?php

namespace App\Application\Articles\Services;

use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\User;
use App\Shared\Results\Result;

interface ArticlePdfExportServiceInterface
{
    public function exportKanjis(EntityId $articleUuid, User $viewer): Result;

    public function exportWords(EntityId $articleUuid, User $viewer): Result;
}
