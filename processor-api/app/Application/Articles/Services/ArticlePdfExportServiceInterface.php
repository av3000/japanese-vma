<?php

namespace App\Application\Articles\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;

interface ArticlePdfExportServiceInterface
{
    public function exportKanjis(EntityId $articleUuid, AuthenticatedUser $authenticatedUser): Result;

    public function exportWords(EntityId $articleUuid, AuthenticatedUser $authenticatedUser): Result;
}
