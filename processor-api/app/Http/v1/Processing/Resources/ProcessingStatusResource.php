<?php

declare(strict_types=1);

namespace App\Http\v1\Processing\Resources;

use App\Domain\Processing\DTOs\ProcessingStateDTO;
use App\Domain\Processing\Enums\ProcessingStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public `processing_status` field.
 *
 * Mirrors App\Application\Processing\Presenters\ProcessingStatePayload field for field; the
 * event test asserts the two serialise identically. The values are read through typed
 * accessors rather than the presenter's array because Scramble derives the OpenAPI schema
 * from these expression types, and the enum accessor is what keeps the generated client's
 * `status` an enumeration (issue #259).
 *
 * @property ProcessingStateDTO $resource
 */
class ProcessingStatusResource extends JsonResource
{
    public function __construct(ProcessingStateDTO $state)
    {
        parent::__construct($state);
    }

    /**
     * @return array{
     *     id: int,
     *     entity_id: string,
     *     type: string,
     *     status: ProcessingStatus,
     *     attempt: int,
     *     metadata: object,
     *     created_at: ?string,
     *     updated_at: ?string
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id(),
            'entity_id' => $this->resource->entityId,
            'type' => $this->resource->taskType,
            'status' => $this->status(),
            'attempt' => $this->attempt(),
            'metadata' => $this->metadata(),
            'created_at' => $this->resource->createdAt?->format('c'),
            'updated_at' => $this->resource->updatedAt?->format('c'),
        ];
    }

    private function id(): int
    {
        return $this->resource->id;
    }

    private function attempt(): int
    {
        return $this->resource->attempt;
    }

    private function status(): ProcessingStatus
    {
        return $this->resource->status;
    }

    /**
     * An empty array must serialise as `{}`, not `[]`, to keep the client type stable.
     */
    private function metadata(): object
    {
        return (object) ($this->resource->metadata ?? []);
    }
}
