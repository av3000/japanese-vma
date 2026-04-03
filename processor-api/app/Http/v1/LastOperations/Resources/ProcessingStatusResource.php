<?php

namespace App\Http\v1\LastOperations\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{
 *     id: int,
 *     type: string,
 *     status: string,
 *     metadata: mixed,
 *     created_at: ?string,
 *     updated_at: ?string
 * } $resource
 */
class ProcessingStatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'],
            'type' => $this->resource['type'],
            'status' => $this->resource['status'],
            'metadata' => $this->resource['metadata'],
            'created_at' => $this->resource['created_at'],
            'updated_at' => $this->resource['updated_at'],
        ];
    }
}
