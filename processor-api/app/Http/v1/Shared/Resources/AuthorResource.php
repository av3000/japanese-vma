<?php

namespace App\Http\v1\Shared\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{id: int, name: string, uuid: string} $resource
 */
class AuthorResource extends JsonResource
{
    /**
     * @return array{id: int, name: string, uuid: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->resource['id'],
            'name' => $this->resource['name'],
            'uuid' => $this->resource['uuid'],
        ];
    }
}
