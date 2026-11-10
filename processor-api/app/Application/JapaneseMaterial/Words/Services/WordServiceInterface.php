<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Words\Services;

use App\Domain\JapaneseMaterial\Words\Queries\WordQueryCriteria;
use App\Shared\Results\Result;

interface WordServiceInterface
{
    public function find(WordQueryCriteria $criteria): Result;

    /**
     * The total find() reports for the same criteria, without loading a page.
     *
     * @return Result<int>
     */
    public function count(WordQueryCriteria $criteria): Result;

    public function findByIdentifier(string $identifier): Result;
}
