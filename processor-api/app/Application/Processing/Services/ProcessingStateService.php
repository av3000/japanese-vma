<?php

declare(strict_types=1);

namespace App\Application\Processing\Services;

use App\Application\LastOperations\Events\AsyncLastOperationStatusUpdated;
use App\Application\Processing\Interfaces\Repositories\ProcessingStateRepositoryInterface;
use App\Domain\Articles\DTOs\ArticleProcessingStateDTO;
use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\Enums\ProcessingTaskType;
use App\Domain\Shared\ValueObjects\EntityId;
use Illuminate\Support\Str;
use Throwable;

final class ProcessingStateService implements ProcessingStateServiceInterface
{
    public const STALE_ERROR_CODE = 'stale';

    public function __construct(
        private readonly ProcessingStateRepositoryInterface $repository,
    ) {
    }

    public function startOrReset(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
        int $contentVersion,
    ): ArticleProcessingStateDTO {
        return $this->broadcast($this->repository->startOrReset($entityType, $entityId, $task, $contentVersion));
    }

    public function markProcessing(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
        int $attempt,
    ): ?ArticleProcessingStateDTO {
        return $this->broadcastIfAny($this->repository->markProcessing($entityType, $entityId, $task, $attempt));
    }

    public function markCompleted(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
        array $metadata,
    ): ?ArticleProcessingStateDTO {
        return $this->broadcastIfAny($this->repository->markCompleted($entityType, $entityId, $task, $metadata));
    }

    public function markFailed(
        ProcessingEntityType $entityType,
        EntityId $entityId,
        ProcessingTaskType $task,
        string $errorCode,
        Throwable|string $error,
        array $metadata = [],
    ): ?ArticleProcessingStateDTO {
        $message = $error instanceof Throwable ? $error->getMessage() : $error;

        if ($error instanceof Throwable) {
            $metadata += ['exception' => $error::class];
        }

        return $this->broadcastIfAny($this->repository->markFailed(
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
    ): ?ArticleProcessingStateDTO {
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

        if ($current === null || $current->isTerminal()) {
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

    private function broadcastIfAny(?ArticleProcessingStateDTO $state): ?ArticleProcessingStateDTO
    {
        return $state === null ? null : $this->broadcast($state);
    }

    private function broadcast(ArticleProcessingStateDTO $state): ArticleProcessingStateDTO
    {
        AsyncLastOperationStatusUpdated::dispatch(...AsyncLastOperationStatusUpdated::argumentsFromDto($state));

        return $state;
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
