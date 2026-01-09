<?php

namespace App\Application\LastOperations\Services;

use App\Application\LastOperations\Events\AsyncLastOperationStatusUpdated;
use App\Application\LastOperations\Interfaces\Repositories\LastOperationRepositoryInterface;
use App\Application\LastOperations\Services\LastOperationServiceInterface;
use App\Domain\Shared\Enums\LastOperationStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\LastOperationState;

class LastOperationService implements LastOperationServiceInterface
{
    public function __construct(
        private readonly LastOperationRepositoryInterface $repository
    ) {}

    /**
     * Get the latest status for a single entity.
     */
    public function getLatestState(EntityId $entityId, string $taskType): ?LastOperationState
    {
        return $this->repository->getLatest($entityId, $taskType);
    }

    public function updateStatus(int $id, LastOperationStatus $status, array $metadata = []): void
    {
        $state = $this->repository->findById($id);

        if ($state) {
            $this->repository->update($state, $status, $metadata);

            // Refresh model to get latest timestamp/data
            $state->refresh();

            // Fire WebSocket Event
            AsyncLastOperationStatusUpdated::dispatch($state);
        }
    }

    /**
     * Get the latest status for a list of entities (Batch processing for Index).
     * Returns a map: [ 'uuid_string' => LastOperationState ]
     */
    public function getBatchLatestStates(array $entityIds, string $taskType): array
    {
        // $entityIds should be an array of EntityId objects or strings.
        // We normalize them to strings for the repository query.
        $uuids = array_map(fn($id) => $id instanceof EntityId ? $id->value() : $id, $entityIds);

        $collection = $this->repository->getBatchLatest($uuids, $taskType);

        // Map the collection key to the UUID for O(1) lookup
        return $collection->keyBy('processable_id')->all();
    }
}
