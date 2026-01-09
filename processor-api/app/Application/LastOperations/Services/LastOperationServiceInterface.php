<?php

namespace App\Application\LastOperations\Services;

use App\Domain\Shared\Enums\LastOperationStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\LastOperationState;

interface LastOperationServiceInterface
{
    /**
     * Get the latest status for a single entity.
     */
    public function getLatestState(EntityId $entityId, string $taskType): ?LastOperationState;

    /**
     * Get the latest status for a list of entities (Batch processing for Index).
     * Returns a map: [ 'uuid_string' => LastOperationState ]
     */
    public function getBatchLatestStates(array $entityIds, string $taskType): array;

    public function updateStatus(int $id, LastOperationStatus $status, array $metadata = []): void;
}
