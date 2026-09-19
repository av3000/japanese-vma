<?php

declare(strict_types=1);

namespace App\Application\LastOperations\Events;

use App\Application\Processing\Presenters\ProcessingStatePayload;
use App\Domain\Articles\DTOs\ArticleProcessingStateDTO;
use App\Domain\Shared\Enums\LastOperationStatus;
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
 * @phpstan-import-type Payload from ProcessingStatePayload
 */
class AsyncLastOperationStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param Payload $snapshot
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
     * @return array{entityUuid: string, snapshot: Payload}
     */
    public static function argumentsFromDto(ArticleProcessingStateDTO $state): array
    {
        return [
            'entityUuid' => $state->entityId,
            'snapshot' => ProcessingStatePayload::fromDto($state),
        ];
    }

    public function status(): LastOperationStatus
    {
        return LastOperationStatus::from($this->snapshot['status']);
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
     * Same shape as the REST `processing_status` field, by construction: both come from
     * ProcessingStatePayload.
     *
     * @return Payload
     */
    public function broadcastWith(): array
    {
        return $this->snapshot;
    }
}
