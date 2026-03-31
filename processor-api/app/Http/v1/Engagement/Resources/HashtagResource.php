<?php

namespace App\Http\v1\Engagement\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HashtagResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => data_get($this->resource, 'id'),
            'content' => data_get($this->resource, 'content'),
            'created_at' => $this->formatDate(data_get($this->resource, 'created_at')),
            'updated_at' => $this->formatDate(data_get($this->resource, 'updated_at')),
        ];
    }

    private function formatDate(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('c');
        }

        return $value;
    }
}
