<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Words\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\JapaneseMaterial\Words\DTOs\WordDetailIncludes;
use App\Shared\Results\Result;

interface WordDetailServiceInterface
{
    public function findByIdentifier(
        string $identifier,
        WordDetailIncludes $includes,
        ?AuthenticatedUser $authenticatedUser = null,
    ): Result;
}
