<?php

declare(strict_types=1);

namespace App\Application\Processing\Interfaces\Repositories;

use App\Domain\Processing\DTOs\ProcessingStateDTO;
use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\Enums\ProcessingTaskType;
use App\Domain\Shared\ValueObjects\EntityId;
use DateTimeInterface;

/**
 * Current processing state per (entity, task). Every method returns a plain DTO, never an
 * Eloquent model, so application code stays free of persistence types. ADR 0001.
 */
interface ProcessingStateRepositoryInterface
{
    /**
     * Upsert the row as `pending` for the given content version, resetting attempt, error and
     * timing columns. Called inside the entity's write transaction before the job is dispatched.
     */
    public function startOrReset(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
        int $contentVersion,
        int $maxAttempts = 3,
    ): ProcessingStateDTO;

    /** Returns null when no row exists for the entity and task. */
    public function markProcessing(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
        int $attempt,
    ): ProcessingStateDTO;

    /**
     * @param array<string, mixed> $metadata counts only; no free text
     */
    public function markCompleted(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
        array $metadata,
    ): ProcessingStateDTO;

    /**
     * @param array<string, mixed> $metadata
     */
    public function markFailed(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
        string $errorCode,
        string $errorMessage,
        array $metadata = [],
    ): ProcessingStateDTO;

    /**
     * The entity's content moved on while this run was queued; the run's result is discarded.
     * Only a non-terminal row still carrying `$staleContentVersion` is touched; returns null when
     * no row exists or the row was left alone, so callers broadcast only real transitions.
     */
    public function markSuperseded(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
        int $staleContentVersion,
    ): ?ProcessingStateDTO;

    public function getCurrent(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
    ): ?ProcessingStateDTO;

    /**
     * One query, exactly one row per entity that has one.
     *
     * @param list<string> $entityIds
     *
     * @return array<string, ProcessingStateDTO> keyed by entity id
     */
    public function getCurrentBatch(
        ProcessingEntityType $entityType,
        array $entityIds,
        ProcessingTaskType $task,
    ): array;

    /**
     * Non-terminal rows not updated since the given instant, oldest first.
     *
     * @return list<ProcessingStateDTO>
     */
    public function getStaleNonTerminal(DateTimeInterface $before): array;
}
