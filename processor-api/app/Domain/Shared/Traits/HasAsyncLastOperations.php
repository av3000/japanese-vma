<?php

declare(strict_types=1);

namespace App\Domain\Shared\Traits;

use App\Domain\Shared\Enums\LastOperationStatus;
use App\Infrastructure\Persistence\Models\LastOperationState;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasAsyncLastOperations
{
    /**
     * Get all processing states for this entity.
     */
    public function lastOperationStates(): MorphMany
    {
        return $this->morphMany(LastOperationState::class, 'processable');
    }

    /**
     * Get the latest state for a specific task type (e.g., 'kanji_extraction').
     * Useful for the API Resource to show the current status.
     */
    public function lastOperationState(string $taskType = 'kanji_extraction'): MorphOne
    {
        return $this->morphOne(LastOperationState::class, 'processable')
            ->where('task_type', $taskType)
            ->latestOfMany();
    }

    /**
     * Helper to create a new state.
     */
    public function startAsyncLastOperation(string $taskType): LastOperationState
    {
        return $this->processingStates()->create([
            'task_type' => $taskType,
            'status' => LastOperationStatus::PENDING,
            'metadata' => [],
        ]);
    }
}
