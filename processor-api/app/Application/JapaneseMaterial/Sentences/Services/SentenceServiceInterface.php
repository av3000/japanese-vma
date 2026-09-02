<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Sentences\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\JapaneseMaterial\Sentences\DTOs\SentenceWriteDTO;
use App\Domain\JapaneseMaterial\Sentences\Queries\SentenceQueryCriteria;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;

interface SentenceServiceInterface
{
    public function find(SentenceQueryCriteria $criteria): Result;

    public function findByIdentifier(string $identifier, bool $withKanjis = true, bool $withWords = true): Result;

    public function create(SentenceWriteDTO $dto, AuthenticatedUser $actor): Result;

    public function update(EntityId $uuid, SentenceWriteDTO $dto, AuthenticatedUser $actor): Result;

    public function delete(EntityId $uuid, AuthenticatedUser $actor): Result;

}
