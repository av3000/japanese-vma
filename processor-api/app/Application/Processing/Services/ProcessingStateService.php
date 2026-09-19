<?php

declare(strict_types=1);

namespace App\Application\Processing\Services;

use App\Application\Processing\Events\ProcessingStatusUpdated;
use App\Application\Processing\Interfaces\Readers\ProcessingOwnerResolverInterface;
use App\Application\Processing\Interfaces\Repositories\ProcessingStateRepositoryInterface;
use App\Domain\Processing\DTOs\ProcessingStateDTO;
use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\Enums\ProcessingTaskType;
use App\Domain\Shared\ValueObjects\EntityId;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PDOException;
use Throwable;

final class ProcessingStateService implements ProcessingStateServiceInterface
{
    public const STALE_ERROR_CODE = 'stale';

    public function __construct(
        private readonly ProcessingStateRepositoryInterface $repository,
        private readonly ProcessingOwnerResolverInterface $owners,
    ) {
    }

    public function startOrReset(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
        int $contentVersion,
    ): ProcessingStateDTO {
        return $this->broadcast($this->repository->startOrReset($entityType, $entityId, $task, $contentVersion));
    }

    public function markProcessing(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
        int $attempt,
    ): ProcessingStateDTO {
        return $this->broadcast($this->repository->markProcessing($entityType, $entityId, $task, $attempt));
    }

    public function markCompleted(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
        array $metadata,
    ): ProcessingStateDTO {
        return $this->broadcast($this->repository->markCompleted($entityType, $entityId, $task, $metadata));
    }

    public function markFailed(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
        string $errorCode,
        Throwable|string $error,
        array $metadata = [],
    ): ProcessingStateDTO {
        $message = $error instanceof Throwable ? self::publicMessageFor($error) : $error;

        if ($error instanceof Throwable) {
            $metadata += ['exception' => $error::class];

            // The full detail belongs in the logs (and Sentry via the exception handler when
            // the job rethrows), never in a payload that reaches every subscriber.
            Log::error('Processing failed', [
                'entity_type' => $entityType->value,
                'entity_id' => $entityId->value(),
                'task_type' => $task->value,
                'error_code' => $errorCode,
                'exception' => $error::class,
                'message' => $error->getMessage(),
            ]);
        }

        return $this->broadcast($this->repository->markFailed(
            $entityType,
            $entityId,
            $task,
            $errorCode,
            self::sanitiseError($message),
            $metadata,
        ));
    }

    public function markSuperseded(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
        int $staleContentVersion,
    ): ?ProcessingStateDTO {
        return $this->broadcastIfAny($this->repository->markSuperseded($entityType, $entityId, $task, $staleContentVersion));
    }

    public function recordFailure(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
        Throwable $exception,
        int $attempt,
    ): void {
        $current = $this->repository->getCurrent($entityType, $entityId, $task);

        if ($current === null) {
            // The only transition allowed to tolerate a missing row: this runs from the queue's
            // failed() hook, where throwing would replace the failure being reported. Loud in
            // the logs instead of silent (#267).
            Log::warning('No processing state to fail', [
                'entity_type' => $entityType->value,
                'entity_id' => $entityId->value(),
                'task_type' => $task->value,
            ]);

            return;
        }

        if ($current->isTerminal()) {
            return;
        }

        $this->markFailed($entityType, $entityId, $task, 'job_failed', $exception, ['attempt' => $attempt]);
    }

    public function sweepStale(int $olderThanSeconds): int
    {
        $stale = $this->repository->getStaleNonTerminal(now()->subSeconds($olderThanSeconds));

        foreach ($stale as $state) {
            $this->markFailed(
                $state->entityType,
                EntityId::from($state->entityId),
                $state->task(),
                self::STALE_ERROR_CODE,
                'no heartbeat',
                ['reason' => 'no heartbeat'],
            );
        }

        return count($stale);
    }

    /**
     * Only `markSuperseded` still has a legitimate null: the run it would supersede already
     * finished, or a newer version owns the row, so there is nothing to write or broadcast.
     */
    private function broadcastIfAny(?ProcessingStateDTO $state): ?ProcessingStateDTO
    {
        return $state === null ? null : $this->broadcast($state);
    }

    private function broadcast(ProcessingStateDTO $state): ProcessingStateDTO
    {
        $ownerUuid = $this->owners->ownerUuid($state->entityType, EntityId::from($state->entityId));

        ProcessingStatusUpdated::dispatch(...ProcessingStatusUpdated::argumentsFromDto($state, $ownerUuid));

        return $state;
    }

    /**
     * What a subscriber may learn about a failure. Database exceptions carry the SQL and its
     * bindings in their message, so they are replaced wholesale; anything else is stripped of
     * a trailing SQL fragment as a belt-and-braces measure.
     */
    private static function publicMessageFor(Throwable $error): string
    {
        if ($error instanceof QueryException || $error instanceof PDOException) {
            return 'Database error';
        }

        return (string) preg_replace('/\s*\(SQL:.*$/su', '', $error->getMessage());
    }

    /**
     * Error text reaches clients over REST and the socket; keep it short and single-line. The
     * full exception belongs in the logs and Sentry.
     */
    private static function sanitiseError(string $message): string
    {
        $collapsed = trim((string) preg_replace('/\s+/u', ' ', $message));

        return Str::limit($collapsed, 300, '...');
    }
}
