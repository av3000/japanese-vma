<?php

namespace App\Application\JapaneseMaterial\Kanjis\Services;

use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji as DomainKanji;
use App\Domain\JapaneseMaterial\Kanjis\Queries\KanjiQueryCriteria;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\KanjiCharacter;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;

interface KanjiServiceInterface
{
    /**
     * Get kanji by UUID.
     *
     * @return Result<DomainKanji>
     */
    public function findByUuid(EntityId $kanjiUuid): Result;

    /**
     * Finds kanjis based on the given criteria.
     *
     * @param KanjiQueryCriteria|null $criteria Optional criteria for filtering.
     *
     * @return Result<\App\Domain\JapaneseMaterial\Kanjis\DTOs\KanjiListResultDTO>
     */
    public function find(?KanjiQueryCriteria $criteria = null): Result;

    // Get a single Kanji by its character.
    // @return Result<Kanji>
    public function findByCharacter(KanjiCharacter $character): Result;
}
