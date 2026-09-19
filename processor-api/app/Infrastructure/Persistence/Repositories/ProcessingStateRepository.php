<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Processing\Interfaces\Repositories\ProcessingStateRepositoryInterface;
use App\Domain\Processing\DTOs\ProcessingStateDTO;
use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\Enums\ProcessingStatus;
use App\Domain\Processing\Enums\ProcessingTaskType;
use App\Domain\Processing\Exceptions\ProcessingStateNotFoundException;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\ProcessingState;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;

final class ProcessingStateRepository implements ProcessingStateRepositoryInterface
{
    public function startOrReset(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
        int $contentVersion,
        int $maxAttempts = 3,
    ): ProcessingStateDTO {
        $state = ProcessingState::query()->updateOrCreate(
            self::key($entityType, $entityId, $task),
            [
                'status' => ProcessingStatus::PENDING,
                'attempt' => 0,
                'max_attempts' => $maxAttempts,
                'content_version' => $contentVersion,
                'started_at' => null,
                'finished_at' => null,
                'error_code' => null,
                'error_message' => null,
                'metadata' => [],
            ],
        );

        return self::toDto($state);
    }

    public function markProcessing(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
        int $attempt,
    ): ProcessingStateDTO {
        return $this->transition($entityType, $entityId, $task, [
            'status' => ProcessingStatus::PROCESSING,
            'attempt' => $attempt,
            'started_at' => now(),
            'finished_at' => null,
            'error_code' => null,
            'error_message' => null,
        ]);
    }

    public function markCompleted(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
        array $metadata,
    ): ProcessingStateDTO {
        return $this->transition($entityType, $entityId, $task, [
            'status' => ProcessingStatus::COMPLETED,
            'finished_at' => now(),
            'error_code' => null,
            'error_message' => null,
            'metadata' => $metadata,
        ]);
    }

    public function markFailed(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
        string $errorCode,
        string $errorMessage,
        array $metadata = [],
    ): ProcessingStateDTO {
        return $this->transition($entityType, $entityId, $task, [
            'status' => ProcessingStatus::FAILED,
            'finished_at' => now(),
            'error_code' => mb_substr($errorCode, 0, 64),
            'error_message' => mb_substr($errorMessage, 0, 300),
            'metadata' => $metadata,
        ]);
    }

    public function markSuperseded(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
        int $staleContentVersion,
    ): ?ProcessingStateDTO {
        $state = $this->findOrFail($entityType, $entityId, $task);

        // A newer run may already own the row; only an in-flight run for this version is stale.
        // Nothing changed means nothing to broadcast, hence null rather than the untouched row.
        if ($state->status->isTerminal() || $state->content_version !== $staleContentVersion) {
            return null;
        }

        $state->fill([
            'status' => ProcessingStatus::SUPERSEDED,
            'finished_at' => now(),
            'error_code' => null,
            'error_message' => null,
        ])->save();

        return self::toDto($state);
    }

    public function getCurrent(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
    ): ?ProcessingStateDTO {
        $state = $this->find($entityType, $entityId, $task);

        return $state === null ? null : self::toDto($state);
    }

    public function getCurrentBatch(
        ProcessingEntityType $entityType,
        array $entityIds,
        ProcessingTaskType $task,
    ): array {
        if ($entityIds === []) {
            return [];
        }

        $states = ProcessingState::query()
            ->where('entity_type', $entityType->value)
            ->where('task_type', $task->value)
            ->whereIn('entity_id', $entityIds)
            ->get();

        $byEntity = [];
        foreach ($states as $state) {
            $byEntity[$state->entity_id] = self::toDto($state);
        }

        return $byEntity;
    }

    public function getStaleNonTerminal(DateTimeInterface $before): array
    {
        return ProcessingState::query()
            ->whereIn('status', ProcessingStatus::nonTerminalValues())
            ->where('updated_at', '<', $before)
            ->orderBy('id')
            ->get()
            ->map(self::toDto(...))
            ->all();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function transition(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
        array $attributes,
    ): ProcessingStateDTO {
        $state = $this->findOrFail($entityType, $entityId, $task);

        $state->fill($attributes)->save();

        return self::toDto($state);
    }

    private function findOrFail(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
    ): ProcessingState {
        return $this->find($entityType, $entityId, $task)
            ?? throw ProcessingStateNotFoundException::for($entityType, $entityId, $task);
    }

    private function find(ProcessingEntityType $entityType, EntityId $entityId, ProcessingTaskType $task): ?ProcessingState
    {
        /** @var ProcessingState|null $state */
        $state = $this->query($entityType, $entityId, $task)->first();

        return $state;
    }

    /**
     * @return Builder<ProcessingState>
     */
    private function query(ProcessingEntityType $entityType, EntityId $entityId, ProcessingTaskType $task): Builder
    {
        return ProcessingState::query()->where(self::key($entityType, $entityId, $task));
    }

    /**
     * @return array{entity_type: string, entity_id: string, task_type: string}
     */
    private static function key(ProcessingEntityType $entityType, EntityId $entityId, ProcessingTaskType $task): array
    {
        return [
            'entity_type' => $entityType->value,
            'entity_id' => $entityId->value(),
            'task_type' => $task->value,
        ];
    }

    public static function toDto(ProcessingState $state): ProcessingStateDTO
    {
        return new ProcessingStateDTO(
            id: (int) $state->id,
            entityType: $state->entity_type,
            entityId: (string) $state->entity_id,
            taskType: (string) $state->task_type,
            status: $state->status,
            attempt: (int) $state->attempt,
            maxAttempts: (int) $state->max_attempts,
            contentVersion: (int) $state->content_version,
            metadata: $state->metadata,
            errorCode: $state->error_code,
            errorMessage: $state->error_message,
            startedAt: $state->started_at ? DateTimeImmutable::createFromInterface($state->started_at) : null,
            finishedAt: $state->finished_at ? DateTimeImmutable::createFromInterface($state->finished_at) : null,
            createdAt: $state->created_at ? DateTimeImmutable::createFromInterface($state->created_at) : null,
            updatedAt: $state->updated_at ? DateTimeImmutable::createFromInterface($state->updated_at) : null,
        );
    }
}
