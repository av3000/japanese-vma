<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Words\Services;

use App\Domain\JapaneseMaterial\Words\DTOs\WordDetailIncludes;
use App\Infrastructure\Persistence\Models\User;
use App\Shared\Results\Result;

interface WordDetailServiceInterface
{
    public function findByIdentifier(
        string $identifier,
        WordDetailIncludes $includes,
        ?User $viewer = null,
    ): Result;
}
