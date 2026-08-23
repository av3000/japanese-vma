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

    public function hasWordStartingWith(string $prefix): bool;

    public function findIdByWord(string $word): ?int;

    /** @return array<int, \App\Domain\JapaneseMaterial\Kanjis\Models\Kanji> */
    public function findRelatedKanjis(int $wordId, int $limit): array;
}
