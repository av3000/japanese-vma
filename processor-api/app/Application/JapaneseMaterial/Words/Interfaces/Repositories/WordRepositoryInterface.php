<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Words\Interfaces\Repositories;

use App\Domain\JapaneseMaterial\Words\DTOs\WordListResultDTO;
use App\Domain\JapaneseMaterial\Words\Models\Word;
use App\Domain\JapaneseMaterial\Words\Queries\WordQueryCriteria;
use App\Domain\Shared\ValueObjects\EntityId;

interface WordRepositoryInterface
{
    public function find(WordQueryCriteria $criteria): WordListResultDTO;

    public function findByUuid(EntityId $uuid): ?Word;

    public function findBySurface(string $surface): ?Word;

    /**
     * Character length of the longest dictionary entry; 0 when the dictionary is empty.
     * Word extraction uses it to bound how far a candidate substring can grow.
     */
    public function maxWordLength(): int;

    /**
     * Resolve many surfaces in one go, for callers that know their whole candidate set up front.
     *
     * @param list<string> $words
     *
     * @return array<string, int> Surface to lowest matching word id, missing surfaces omitted.
     */
    public function findIdsByWords(array $words): array;

    /** @return array<int, \App\Domain\JapaneseMaterial\Kanjis\Models\Kanji> */
    public function findRelatedKanjis(int $wordId, int $limit): array;
}
