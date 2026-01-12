<?php

namespace App\Http\v1\Engagement\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LikeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'value' => $this->value,
            'created_at' => $this->created_at,

            'user' => $this->user_id ? [
                'id' => $this->user_id,
                'uuid' => $this->user_uuid,
                'name' => $this->user_name,
            ] : null,
        ];
    }
}
