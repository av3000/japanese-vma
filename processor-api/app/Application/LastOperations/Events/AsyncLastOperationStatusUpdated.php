<?php

declare(strict_types=1);

namespace App\Application\LastOperations\Events;

use App\Domain\Articles\DTOs\ArticleProcessingStateDTO;
use App\Domain\Shared\Enums\LastOperationStatus;
use App\Http\v1\LastOperations\Resources\ProcessingStatusResource;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Pushed to clients every time a processing-state row is written.
 *
 * Broadcast synchronously from a snapshot taken at write time (audit F-08). Alias and channel
 * are unchanged from the pre-ADR-0001 event so the frontend needs no change; P4-1 versions it.
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

    public static function fromDto(ArticleProcessingStateDTO $state): self
    {
        return new self(...self::argumentsFromDto($state));
    }

    /**
     * @return array{entityUuid: string, snapshot: Snapshot}
     */
    public static function argumentsFromDto(ArticleProcessingStateDTO $state): array
    {
        return [
            'entityUuid' => $state->entityId,
            'snapshot' => [
                'id' => $state->id,
                'type' => $state->taskType,
                'status' => $state->status,
                'metadata' => $state->metadata ?? [],
                'created_at' => $state->createdAt?->format('c'),
                'updated_at' => $state->updatedAt?->format('c'),
            ],
        ];
    }

    public function status(): LastOperationStatus
    {
        return $this->snapshot['status'];
    }

    /**
     * Channel: "private-last_operations.{entity uuid}".
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
