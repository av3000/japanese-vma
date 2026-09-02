<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Sentences\Interfaces\Repositories;

use App\Domain\JapaneseMaterial\Sentences\DTOs\SentenceListResultDTO;
use App\Domain\JapaneseMaterial\Sentences\DTOs\SentenceWriteDTO;
use App\Domain\JapaneseMaterial\Sentences\Models\Sentence;
use App\Domain\JapaneseMaterial\Sentences\Queries\SentenceQueryCriteria;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;

interface SentenceRepositoryInterface
{
    public function find(SentenceQueryCriteria $criteria): SentenceListResultDTO;

    public function findByUuid(EntityId $uuid, bool $withKanjis = false, bool $withWords = false): ?Sentence;

    public function findByLegacyId(int $id, bool $withKanjis = false, bool $withWords = false): ?Sentence;

    public function create(SentenceWriteDTO $dto, UserId $ownerId, EntityId $uuid): Sentence;

    public function updateContent(int $sentenceId, SentenceWriteDTO $dto): void;

    /**
     * @param array<int, int> $kanjiIds
     */
    public function syncKanjis(int $sentenceId, array $kanjiIds): void;

    /**
     * @param array<int, int> $wordIds
     */
    public function syncWords(int $sentenceId, array $wordIds): void;

}
