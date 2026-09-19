<?php

declare(strict_types=1);

namespace App\Application\Processing\Presenters;

use App\Domain\Articles\DTOs\ArticleProcessingStateDTO;

/**
 * The one definition of the public `processing_status` shape. The HTTP resource and the
 * broadcast event both serialise through here, so REST and socket can never drift apart, and
 * Application never has to reach into the HTTP layer to build a payload (issue #259).
 *
 * @phpstan-type Payload array{
 *     id: int,
 *     type: string,
 *     status: string,
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
    public static function fromDto(ArticleProcessingStateDTO $state): array
    {
        return [
            'id' => $state->id,
            'type' => $state->taskType,
            'status' => $state->status->value,
            // An empty array must serialise as `{}`, not `[]`, to keep the client type stable.
            'metadata' => (object) ($state->metadata ?? []),
            'created_at' => $state->createdAt?->format('c'),
            'updated_at' => $state->updatedAt?->format('c'),
        ];
    }
}
