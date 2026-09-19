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
    public const OWNER_CHANNEL_PREFIX = 'App.User.';

    /**
     * @param ?string $ownerUuid when known, the event is also pushed on the owner's private
     *                           channel so an owner dashboard needs one subscription for
     *                           every article it lists (ADR 0002 point 5)
     */
    public function __construct(
        public readonly string $entityUuid,
        public readonly array $snapshot,
        public readonly ?string $ownerUuid = null,
    ) {
    }

    public static function fromDto(ArticleProcessingStateDTO $state, ?string $ownerUuid = null): self
    {
        return new self(...self::argumentsFromDto($state, $ownerUuid));
    }

    /**
     * @return array{entityUuid: string, snapshot: Payload, ownerUuid: ?string}
     */
    public static function argumentsFromDto(ArticleProcessingStateDTO $state, ?string $ownerUuid = null): array
    {
        return [
            'entityUuid' => $state->entityId,
            'snapshot' => ProcessingStatePayload::fromDto($state),
            'ownerUuid' => $ownerUuid,
        ];
    }

    public function status(): LastOperationStatus
    {
        return LastOperationStatus::from($this->snapshot['status']);
    }

    /**
     * Channels: "private-last_operations.{entity uuid}" always; "private-App.User.{owner uuid}"
     * when the owner is known.
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('last_operations.'.$this->entityUuid)];

        if ($this->ownerUuid !== null) {
            $channels[] = new PrivateChannel(self::OWNER_CHANNEL_PREFIX.$this->ownerUuid);
        }

        return $channels;
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
