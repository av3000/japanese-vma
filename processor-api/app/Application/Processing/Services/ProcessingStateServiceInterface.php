<?php

declare(strict_types=1);

namespace App\Application\Processing\Services;

use App\Domain\Processing\DTOs\ProcessingStateDTO;
use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\Enums\ProcessingTaskType;
use App\Domain\Shared\ValueObjects\EntityId;
use Throwable;

/**
 * Application seam over the processing-state repository. Every transition that changes a row
 * is broadcast to clients from a write-time snapshot, so callers never need to remember to.
 *
 * Transitions throw ProcessingStateNotFoundException when the row does not exist rather than
 * returning silently (#267); every transition follows a `startOrReset` that created one, so a
 * missing row is a caller bug, not a state the system should absorb.
 */
interface ProcessingStateServiceInterface
{
    public function startOrReset(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
        int $contentVersion,
    ): ProcessingStateDTO;

    public function markProcessing(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
        int $attempt,
    ): ProcessingStateDTO;

    /**
     * @param array<string, mixed> $metadata
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
        Throwable|string $error,
        array $metadata = [],
    ): ProcessingStateDTO;

    public function markSuperseded(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
        int $staleContentVersion,
    ): ?ProcessingStateDTO;

    /**
     * From a job's failed() hook: fail the row only if it is still non-terminal, so a run that
     * already recorded its own outcome is not rewritten.
     */
    public function recordFailure(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
        Throwable $exception,
        int $attempt,
    ): void;

    /**
     * Mark every pending or processing row older than the threshold as `failed`.
     *
     * @return int number of rows swept
     */
    public function sweepStale(int $olderThanSeconds): int;
}
