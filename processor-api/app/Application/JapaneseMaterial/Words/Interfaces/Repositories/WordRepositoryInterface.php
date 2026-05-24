<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Words\Interfaces\Repositories;

interface WordRepositoryInterface
{
    public function hasWordStartingWith(string $prefix): bool;

    public function findIdByWord(string $word): ?int;
}
