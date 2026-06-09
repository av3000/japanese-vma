<?php

namespace App\Application\JapaneseMaterial\Kanjis\Interfaces\Repositories;

use App\Domain\JapaneseMaterial\Kanjis\DTOs\KanjiListResultDTO;
use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji;
use App\Domain\JapaneseMaterial\Kanjis\Queries\KanjiQueryCriteria;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\KanjiCharacter;
use App\Domain\Shared\ValueObjects\EntityId;

interface KanjiRepositoryInterface
{
    // Finds a single Kanji by its UUID.
    public function findByUuid(EntityId $uuid): ?Kanji;

    // Finds a single Kanji by its character.
    public function findByCharacter(KanjiCharacter $character): ?Kanji;

    public function find(?KanjiQueryCriteria $criteria = null): KanjiListResultDTO;

    public function findIdsByCharacters(array $characters): array;

    public function findByIds(array $ids): array;

    public function findByCharacters(array $characters): array;

    /**
     * Finds multiple Kanjis by their characters.
     *
     * @param KanjiCharacter[] $characters An array of KanjiCharacter value objects.
     *
     * @return \App\Domain\JapaneseMaterial\Kanjis\Models\Kanji[] An array of DomainKanji models.
     */
    public function findManyByCharacters(array $characters): array;
}
