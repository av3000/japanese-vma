<?php

declare(strict_types=1);

namespace App\Application\LastOperations\Events;

use App\Infrastructure\Persistence\Models\LastOperationState;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AsyncLastOperationStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly LastOperationState $operationState
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        // Channel: "last_operations.{uuid}"
        return [
            new PrivateChannel('last_operations.' . $this->operationState->processable_id),
        ];
    }

    /**
     * The event's broadcast name.
     * Use a distinct alias so you don't have to bind full class paths in the frontend.
     */
    public function broadcastAs(): string
    {
        return 'OperationStatusUpdated';
    }

    /**
     * Get the data to broadcast.
     * CRITICAL: Keep this consistent with your API Resource output.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->operationState->id,
            'type' => $this->operationState->task_type,
            'status' => $this->operationState->status->value,
            'metadata' => $this->operationState->metadata,
            'created_at' => $this->operationState->created_at?->toIso8601String(),
            'updated_at' => $this->operationState->updated_at->toIso8601String(),
        ];
    }
}
