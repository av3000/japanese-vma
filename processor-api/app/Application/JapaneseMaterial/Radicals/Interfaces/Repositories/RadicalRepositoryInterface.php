<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Radicals\Interfaces\Repositories;

use App\Domain\JapaneseMaterial\Radicals\DTOs\RadicalListResultDTO;
use App\Domain\JapaneseMaterial\Radicals\Models\Radical;
use App\Domain\JapaneseMaterial\Radicals\Queries\RadicalQueryCriteria;
use App\Domain\Shared\ValueObjects\EntityId;

interface RadicalRepositoryInterface
{
    public function find(RadicalQueryCriteria $criteria): RadicalListResultDTO;

    public function findByUuid(EntityId $uuid, bool $withKanjis = false): ?Radical;

    public function findByLegacyId(int $id, bool $withKanjis = false): ?Radical;
}
