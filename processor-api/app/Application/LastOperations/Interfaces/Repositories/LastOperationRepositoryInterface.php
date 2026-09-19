<?php

namespace App\Application\LastOperations\Interfaces\Repositories;

use App\Domain\Shared\Enums\LastOperationStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\LastOperationState;
use DateTimeInterface;
use Illuminate\Support\Collection;

interface LastOperationRepositoryInterface
{
    // TODO: create strict types for the entityType and taskType
    public function findById(int $id): ?LastOperationState;

    public function update(LastOperationState $state, LastOperationStatus $status, array $metadata = []): void;

    public function start(EntityId $entityId, string $entityType, string $taskType): LastOperationState;

    public function getLatest(EntityId $entityId, string $taskType): ?LastOperationState;

    public function getBatchLatest(array $uuids, string $taskType): Collection;

    /**
     * Latest row for the entity and task that a retry may reuse: pending, processing or failed.
     */
    public function getLatestRetryable(EntityId $entityId, string $taskType): ?LastOperationState;

    /**
     * Latest row for the entity and task that has not reached a terminal status.
     */
    public function getLatestNonTerminal(EntityId $entityId, string $taskType): ?LastOperationState;

    /**
     * Non-terminal rows whose last update is older than the given instant.
     *
     * @return Collection<int, LastOperationState>
     */
    public function getStaleNonTerminal(DateTimeInterface $before): Collection;
}
