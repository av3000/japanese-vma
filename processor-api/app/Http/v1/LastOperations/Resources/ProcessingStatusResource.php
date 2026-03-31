<?php

namespace App\Http\v1\LastOperations\Resources;

use App\Infrastructure\Persistence\Models\LastOperationState;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property LastOperationState $resource
 */
class ProcessingStatusResource extends JsonResource
{
    public function __construct(LastOperationState $lastOperation)
    {
        parent::__construct($lastOperation);
    }

    public function toArray(Request $request): array
    {
        /** @var LastOperationState $lastOperation */
        $lastOperation = $this->resource;

        return [
            'id' => $lastOperation->id,
            'type' => $lastOperation->task_type,
            'status' => $lastOperation->status->value,
            'metadata' => $lastOperation->metadata,
            'created_at' => $lastOperation->created_at?->toIso8601String(),
            'updated_at' => $lastOperation->updated_at?->toIso8601String(),
        ];
    }
}
