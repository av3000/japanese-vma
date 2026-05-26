<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Sentences\Services;

use App\Domain\JapaneseMaterial\Sentences\Queries\SentenceQueryCriteria;
use App\Shared\Results\Result;

interface SentenceServiceInterface
{
    public function find(SentenceQueryCriteria $criteria): Result;

    public function findByIdentifier(string $identifier, bool $withKanjis = true): Result;
}
