<?php

namespace App\Http\v1\Engagement\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{id: int|string, content: string, created_at?: mixed, updated_at?: mixed}|object $resource
 */
class HashtagResource extends JsonResource
{
    /**
     * @return array{id: int|string|null, content: string|null, created_at: string|null, updated_at: string|null}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => data_get($this->resource, 'id'),
            'content' => data_get($this->resource, 'content'),
            'created_at' => $this->formatDate(data_get($this->resource, 'created_at')),
            'updated_at' => $this->formatDate(data_get($this->resource, 'updated_at')),
        ];
    }

    private function formatDate(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('c');
        }

        return is_string($value) ? $value : null;
    }
}
