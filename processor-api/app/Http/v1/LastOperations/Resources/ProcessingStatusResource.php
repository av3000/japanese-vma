<?php

namespace App\Http\v1\LastOperations\Resources;

use App\Domain\Shared\Enums\LastOperationStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{
 *     id: int,
 *     type: string,
 *     status: LastOperationStatus,
 *     metadata: array<string, mixed>,
 *     created_at: ?string,
 *     updated_at: ?string
 * } $resource
 */
class ProcessingStatusResource extends JsonResource
{
    /**
     * @return array{
     *     id: int,
     *     type: string,
     *     status: LastOperationStatus,
     *     metadata: object,
     *     created_at: ?string,
     *     updated_at: ?string
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->resource['id'],
            'type' => $this->resource['type'],
            'status' => $this->status(),
            'metadata' => $this->metadata(),
            'created_at' => $this->resource['created_at'],
            'updated_at' => $this->resource['updated_at'],
        ];
    }

    private function status(): LastOperationStatus
    {
        return $this->resource['status'];
    }

    /**
     * @return object
     */
    private function metadata(): object
    {
        /** @var array<string, mixed> $metadata */
        $metadata = $this->resource['metadata'];

        return (object) $metadata;
    }
}
