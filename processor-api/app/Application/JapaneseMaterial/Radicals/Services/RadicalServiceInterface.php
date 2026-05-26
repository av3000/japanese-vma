<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Radicals\Services;

use App\Domain\JapaneseMaterial\Radicals\Queries\RadicalQueryCriteria;
use App\Shared\Results\Result;

interface RadicalServiceInterface
{
    public function find(RadicalQueryCriteria $criteria): Result;

    public function findByIdentifier(string $identifier, bool $withKanjis = true): Result;
}
