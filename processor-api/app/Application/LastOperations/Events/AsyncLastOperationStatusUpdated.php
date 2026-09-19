<?php

declare(strict_types=1);

namespace App\Application\LastOperations\Events;

use App\Domain\Shared\Enums\LastOperationStatus;
use App\Http\v1\LastOperations\Resources\ProcessingStatusResource;
use App\Infrastructure\Persistence\Models\LastOperationState;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Pushed to clients every time an operation row is written.
 *
 * Broadcast synchronously from a snapshot taken at write time. The previous queued variant
 * re-hydrated the Eloquent model when the broadcast job ran, which on a single worker process
 * meant clients received the final status twice and the intermediate ones never (audit F-08).
 *
 * @phpstan-type Snapshot array{
 *     id: int,
 *     type: string,
 *     status: LastOperationStatus,
 *     metadata: array<string, mixed>,
 *     created_at: ?string,
 *     updated_at: ?string
 * }
 */
class AsyncLastOperationStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param Snapshot $snapshot
     */
    public function __construct(
        public readonly string $entityUuid,
        public readonly array $snapshot,
    ) {
    }

    public static function fromState(LastOperationState $state): self
    {
        return new self(
            entityUuid: (string) $state->processable_id,
            snapshot: [
                'id' => (int) $state->id,
                'type' => (string) $state->task_type,
                'status' => $state->status,
                'metadata' => $state->metadata ?? [],
                'created_at' => $state->created_at?->toIso8601String(),
                'updated_at' => $state->updated_at?->toIso8601String(),
            ],
        );
    }

    public function status(): LastOperationStatus
    {
        return $this->snapshot['status'];
    }

    /**
     * Channel: "private-last_operations.{article uuid}".
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('last_operations.'.$this->entityUuid),
        ];
    }

    /**
     * Distinct alias so the frontend does not bind to the PHP class path.
     */
    public function broadcastAs(): string
    {
        return 'OperationStatusUpdated';
    }

    /**
     * Same shape as the REST `processing_status` field. Keep the two in sync via the resource.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return (new ProcessingStatusResource($this->snapshot))->resolve();
    }
}
