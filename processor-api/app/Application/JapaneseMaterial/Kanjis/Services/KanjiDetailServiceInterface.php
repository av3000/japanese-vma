<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Kanjis\Services;

use App\Domain\JapaneseMaterial\Kanjis\DTOs\KanjiDetailIncludes;
use App\Infrastructure\Persistence\Models\User;
use App\Shared\Results\Result;

interface KanjiDetailServiceInterface
{
    public function findByIdentifier(
        string $identifier,
        KanjiDetailIncludes $includes,
        ?User $viewer = null,
    ): Result;
}
