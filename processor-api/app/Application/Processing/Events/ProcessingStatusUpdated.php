<?php

declare(strict_types=1);

namespace App\Application\Processing\Events;

use App\Application\Processing\Presenters\ProcessingStatePayload;
use App\Domain\Processing\DTOs\ProcessingStateDTO;
use App\Domain\Processing\Enums\ProcessingStatus;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Pushed to clients every time a processing-state row is written.
 *
 * Broadcast synchronously from a snapshot taken at write time (audit F-08). The alias and the
 * channel were versioned with the ADR 0001 vocabulary in #266: `OperationStatusUpdated` on
 * `last_operations.{uuid}` became `ProcessingStatusUpdated` on `processing_states.{uuid}`. A
 * browser tab loaded before that deploy hears nothing on the new channel and falls back to
 * polling until it reloads.
 *
 * @phpstan-import-type Payload from ProcessingStatePayload
 */
class ProcessingStatusUpdated implements ShouldBroadcastNow
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

    public static function fromDto(ProcessingStateDTO $state, ?string $ownerUuid = null): self
    {
        return new self(...self::argumentsFromDto($state, $ownerUuid));
    }

    /**
     * @return array{entityUuid: string, snapshot: Payload, ownerUuid: ?string}
     */
    public static function argumentsFromDto(ProcessingStateDTO $state, ?string $ownerUuid = null): array
    {
        return [
            'entityUuid' => $state->entityId,
            'snapshot' => ProcessingStatePayload::fromDto($state),
            'ownerUuid' => $ownerUuid,
        ];
    }

    public function status(): ProcessingStatus
    {
        return ProcessingStatus::from($this->snapshot['status']);
    }

    /**
     * Channels: "private-processing_states.{entity uuid}" always; "private-App.User.{owner uuid}"
     * when the owner is known.
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('processing_states.'.$this->entityUuid)];

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
        return 'ProcessingStatusUpdated';
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
