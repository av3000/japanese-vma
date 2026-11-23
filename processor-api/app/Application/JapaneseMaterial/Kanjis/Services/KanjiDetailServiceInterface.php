<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Kanjis\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\JapaneseMaterial\Kanjis\DTOs\KanjiDetailIncludes;
use App\Shared\Results\Result;

interface KanjiDetailServiceInterface
{
    public function findByIdentifier(
        string $identifier,
        KanjiDetailIncludes $includes,
        ?AuthenticatedUser $authenticatedUser = null,
    ): Result;
}
