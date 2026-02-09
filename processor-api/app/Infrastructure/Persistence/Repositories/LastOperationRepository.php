<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\LastOperations\Interfaces\Repositories\LastOperationRepositoryInterface;
use App\Domain\Shared\Enums\LastOperationStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\LastOperationState;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

class LastOperationRepository implements LastOperationRepositoryInterface
{
    public function start(EntityId $entityId, string $entityType, string $taskType): LastOperationState
    {
        // We need to resolve the generic entity string to the actual Persistence Class
        $modelClass = Relation::getMorphedModel($entityType) ?? $entityType;

        return LastOperationState::create([
            'processable_id' => $entityId->value(),
            'processable_type' => $modelClass,
            'task_type' => $taskType,
            'status' => LastOperationStatus::PENDING,
            'metadata' => [],
        ]);
    }

    public function getLatest(EntityId $entityId, string $taskType): ?LastOperationState
    {
        return LastOperationState::where('processable_id', $entityId->value())
            ->where('task_type', $taskType)
            ->latest()
            ->first();
    }

    public function getBatchLatest(array $uuids, string $taskType): Collection
    {
        // Optimized query to fetch statuses for multiple items at once
        // In a high-traffic system, you might use a subquery to ensure strictly "latest".
        // For now, fetching matches and filtering in memory for the page size (e.g. 20 items) is efficient.

        return LastOperationState::query()
            ->whereIn('processable_id', $uuids)
            ->where('task_type', $taskType)
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('processable_id'); // Laravel collection method to keep only the first (latest) per ID
    }

    public function findById(int $id): ?LastOperationState
    {
        return LastOperationState::find($id);
    }

    public function update(LastOperationState $state, LastOperationStatus $status, array $metadata = []): void
    {
        $state->update([
            'status' => $status,
            'metadata' => array_merge($state->metadata ?? [], $metadata) // Merge new metadata with old
        ]);
    }
}
