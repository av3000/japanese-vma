<?php

namespace App\Application\LastOperations\Services;

use App\Domain\Shared\Enums\LastOperationStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\LastOperationState;
use Throwable;

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

    public function startOperation(EntityId $entityId, string $entityType, string $taskType): LastOperationState;

    /**
     * Open an attempt of an operation and return its row already in `processing`.
     *
     * The first attempt creates a new row. Later attempts reuse the latest pending, processing
     * or failed row for the same entity and task, so a retry does not surface as a fresh
     * `pending` operation to clients.
     */
    public function beginAttempt(EntityId $entityId, string $entityType, string $taskType, int $attempt): LastOperationState;

    /**
     * Mark an operation `failed` from a job's failed() hook.
     *
     * Uses the row id when the attempt recorded one, otherwise the latest non-terminal row for
     * the entity and task. Does nothing when no such row exists.
     */
    public function recordFailure(EntityId $entityId, string $taskType, ?int $operationStateId, Throwable $exception, int $attempts): void;

    /**
     * Mark every pending or processing row older than the threshold as `failed`.
     *
     * @return int number of rows swept
     */
    public function sweepStale(int $olderThanSeconds): int;
}
