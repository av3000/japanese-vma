<?php

namespace App\Http\v1\Shared\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{
 *     page: int,
 *     per_page: int,
 *     total: int,
 *     last_page: int,
 *     has_more: bool
 * } $resource
 */
class PaginationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'page' => (int) $this->resource['page'],
            'per_page' => (int) $this->resource['per_page'],
            'total' => (int) $this->resource['total'],
            'last_page' => (int) $this->resource['last_page'],
            'has_more' => (bool) $this->resource['has_more'],
        ];
    }
}
