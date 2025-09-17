<?php
namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Articles\Interfaces\Repositories\KanjiRepositoryInterface;
use App\Infrastructure\Persistence\Models\Kanji as PersistenceKanji;
use App\Domain\Japanese\Models\Kanji as DomainKanji;

class KanjiRepository implements KanjiRepositoryInterface
{
    public function findIdsByCharacters(array $characters): array
    {
        return PersistenceKanji::whereIn('kanji', $characters)
            ->pluck('id')
            ->toArray();
    }

    public function findByIds(array $ids): array
    {
        $entities = PersistenceKanji::whereIn('id', $ids)->get();

        // TODO: create and use kanji mapper
        // TODO: create and use kanji domain object with value objects
        return $entities;
    }

    public function findByCharacters(array $characters): array
    {
        $entities = PersistenceKanji::whereIn('kanji', $characters)->get();

        // TODO: create and use kanji mapper
        // TODO: create and use kanji domain object with value objects
        return $entities;
    }
}
