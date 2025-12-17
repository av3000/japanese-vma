<?php

namespace App\Application\JapaneseMaterial\Kanjis\Interfaces\Repositories;

use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji;
use App\Domain\JapaneseMaterial\Kanjis\Models\Kanjis;
use App\Domain\JapaneseMaterial\Kanjis\Queries\KanjiQueryCriteria;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\KanjiCharacter;

interface KanjiRepositoryInterface
{
    // Finds a single Kanji by its UUID.
    public function findByUuid(EntityId $uuid): ?Kanji;

    // Finds a single Kanji by its character.
    public function findByCharacter(KanjiCharacter $character): ?Kanji;

    // Finds Kanjis based on the given criteria.
    public function find(?KanjiQueryCriteria $criteria = null): Kanjis;

    public function findIdsByCharacters(array $characters): array;
    public function findByIds(array $ids): array;
    public function findByCharacters(array $characters): array;
}
