<?php

declare(strict_types=1);

namespace App\Application\Processing\Presenters;

use App\Domain\Processing\DTOs\ProcessingStateDTO;

/**
 * The one definition of the public `processing_status` shape. The HTTP resource and the
 * broadcast event both serialise through here, so REST and socket can never drift apart, and
 * Application never has to reach into the HTTP layer to build a payload (issue #259).
 *
 * @phpstan-type Payload array{
 *     id: int,
 *     entity_id: string,
 *     type: string,
 *     status: string,
 *     sequence: int,
 *     attempt: int,
 *     max_attempts: int,
 *     metadata: object,
 *     created_at: ?string,
 *     updated_at: ?string
 * }
 */
final class ProcessingStatePayload
{
    /**
     * @return Payload
     */
    public static function fromDto(ProcessingStateDTO $state): array
    {
        return [
            'id' => $state->id,
            // Lets a subscriber on a channel that carries many entities (the owner channel)
            // patch the right cache entry.
            'entity_id' => $state->entityId,
            'type' => $state->taskType,
            'status' => $state->status->value,
            // Monotonic per row: a subscriber drops any event whose sequence it has already
            // seen, which is the only defence against out-of-order socket delivery (#261).
            'sequence' => $state->sequence,
            'attempt' => $state->attempt,
            'max_attempts' => $state->maxAttempts,
            // An empty array must serialise as `{}`, not `[]`, to keep the client type stable.
            'metadata' => (object) ($state->metadata ?? []),
            'created_at' => $state->createdAt?->format('c'),
            'updated_at' => $state->updatedAt?->format('c'),
        ];
    }
}
