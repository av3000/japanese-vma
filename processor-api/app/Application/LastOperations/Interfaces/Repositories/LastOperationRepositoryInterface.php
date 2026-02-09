<?php

namespace App\Application\LastOperations\Interfaces\Repositories;

use App\Domain\Shared\Enums\LastOperationStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\LastOperationState;
use Illuminate\Support\Collection;

interface LastOperationRepositoryInterface
{

    public function findById(int $id): ?LastOperationState;

    public function update(LastOperationState $state, LastOperationStatus $status, array $metadata = []): void;

    public function start(EntityId $entityId, string $entityType, string $taskType): LastOperationState;

    public function getLatest(EntityId $entityId, string $taskType): ?LastOperationState;

    public function getBatchLatest(array $uuids, string $taskType): Collection;
}
