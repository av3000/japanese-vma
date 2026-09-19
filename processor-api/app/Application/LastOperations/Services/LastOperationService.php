<?php

namespace App\Application\LastOperations\Services;

use App\Application\LastOperations\Events\AsyncLastOperationStatusUpdated;
use App\Application\LastOperations\Interfaces\Repositories\LastOperationRepositoryInterface;
use App\Domain\Shared\Enums\LastOperationStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\LastOperationState;
use Illuminate\Support\Str;
use Throwable;

class LastOperationService implements LastOperationServiceInterface
{
    public function __construct(
        private readonly LastOperationRepositoryInterface $repository
    ) {
    }

    /**
     * Get the latest status for a single entity.
     */
    public function getLatestState(EntityId $entityId, string $taskType): ?LastOperationState
    {
        return $this->repository->getLatest($entityId, $taskType);
    }

    // TODO: entityType should be some defined value from consts list of entities which can be queues for operation. 'article', as an example value.
    // TODO: taskType should be typed values from consts list of actions that can be done with specific instance. For example, 'article' entityType can have 'kanji_extraction', 'words_extraction' or other possible actions which will be queable and takes longer time of period than simple update of entity metadata.
    public function startOperation(EntityId $entityId, string $entityType, string $taskType): LastOperationState
    {
        $state = $this->repository->start(
            $entityId,
            $entityType,
            $taskType
        );

        // Clients should see `pending` too, not only the transitions that follow.
        AsyncLastOperationStatusUpdated::dispatch(...self::snapshotArguments($state));

        return $state;
    }

    public function updateStatus(int $id, LastOperationStatus $status, array $metadata = []): void
    {
        $state = $this->repository->findById($id);

        if ($state) {
            $this->transition($state, $status, $metadata);
        }
    }

    public function beginAttempt(EntityId $entityId, string $entityType, string $taskType, int $attempt): LastOperationState
    {
        $state = $attempt > 1
            ? $this->repository->getLatestRetryable($entityId, $taskType)
            : null;

        if ($state === null) {
            $state = $this->repository->start($entityId, $entityType, $taskType);
        }

        $this->transition($state, LastOperationStatus::PROCESSING, ['attempts' => $attempt]);

        return $state;
    }

    public function recordFailure(EntityId $entityId, string $taskType, ?int $operationStateId, Throwable $exception, int $attempts): void
    {
        $state = $operationStateId !== null
            ? $this->repository->findById($operationStateId)
            : null;

        $state ??= $this->repository->getLatestNonTerminal($entityId, $taskType);

        if ($state === null) {
            return;
        }

        $this->transition($state, LastOperationStatus::FAILED, [
            'error' => self::sanitiseError($exception->getMessage()),
            'exception' => $exception::class,
            'attempts' => $attempts,
        ]);
    }

    public function sweepStale(int $olderThanSeconds): int
    {
        $stale = $this->repository->getStaleNonTerminal(now()->subSeconds($olderThanSeconds));

        foreach ($stale as $state) {
            $this->transition($state, LastOperationStatus::FAILED, [
                'error' => 'stale',
                'reason' => 'no heartbeat',
            ]);
        }

        return $stale->count();
    }

    /**
     * One UPDATE, then broadcast a snapshot of the in-memory model. Eloquent already applies the
     * new status, merged metadata and `updated_at` to the instance on save, so no re-read is needed
     * and the payload cannot drift from what was just written.
     */
    private function transition(LastOperationState $state, LastOperationStatus $status, array $metadata): void
    {
        $this->repository->update($state, $status, $metadata);

        AsyncLastOperationStatusUpdated::dispatch(...self::snapshotArguments($state));
    }

    /**
     * @return array{entityUuid: string, snapshot: array<string, mixed>}
     */
    private static function snapshotArguments(LastOperationState $state): array
    {
        $event = AsyncLastOperationStatusUpdated::fromState($state);

        return ['entityUuid' => $event->entityUuid, 'snapshot' => $event->snapshot];
    }

    /**
     * Metadata is delivered to clients over REST and the socket; keep it short and single-line.
     * The full exception belongs in the logs and Sentry.
     */
    private static function sanitiseError(string $message): string
    {
        $collapsed = trim((string) preg_replace('/\s+/u', ' ', $message));

        return Str::limit($collapsed, 300, '...');
    }

    /**
     * Get the latest status for a list of entities (Batch processing for Index).
     * Returns a map: [ 'uuid_string' => LastOperationState ]
     */
    public function getBatchLatestStates(array $entityIds, string $taskType): array
    {
        // $entityIds should be an array of EntityId objects or strings.
        // We normalize them to strings for the repository query.
        $uuids = array_map(fn ($id) => $id instanceof EntityId ? $id->value() : $id, $entityIds);

        $collection = $this->repository->getBatchLatest($uuids, $taskType);

        // Map the collection key to the UUID for O(1) lookup
        return $collection->keyBy('processable_id')->all();
    }
}
