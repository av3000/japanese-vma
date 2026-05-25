<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\JapaneseMaterial\Words\Interfaces\Repositories\WordRepositoryInterface;
use App\Infrastructure\Persistence\Models\Word as PersistenceWord;

class WordRepository implements WordRepositoryInterface
{
    public function hasWordStartingWith(string $prefix): bool
    {
        return PersistenceWord::query()
            ->where('word', 'like', $prefix.'%')
            ->exists();
    }

    public function findIdByWord(string $word): ?int
    {
        $id = PersistenceWord::query()
            ->where('word', $word)
            ->value('id');

        return $id !== null ? (int) $id : null;
    }
}
