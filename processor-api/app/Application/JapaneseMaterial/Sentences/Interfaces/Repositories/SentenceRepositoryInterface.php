<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Sentences\Interfaces\Repositories;

use App\Domain\JapaneseMaterial\Sentences\DTOs\SentenceListResultDTO;
use App\Domain\JapaneseMaterial\Sentences\Models\Sentence;
use App\Domain\JapaneseMaterial\Sentences\Queries\SentenceQueryCriteria;
use App\Domain\Shared\ValueObjects\EntityId;

interface SentenceRepositoryInterface
{
    public function find(SentenceQueryCriteria $criteria): SentenceListResultDTO;

    public function findByUuid(EntityId $uuid, bool $withKanjis = false): ?Sentence;

    public function findByLegacyId(int $id, bool $withKanjis = false): ?Sentence;
}
