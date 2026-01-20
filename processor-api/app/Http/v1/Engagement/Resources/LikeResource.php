<?php

namespace App\Http\v1\Engagement\Resources;

use App\Domain\Engagement\Models\Like;
use App\Domain\Users\Models\LikeUser;
use Illuminate\Http\Resources\Json\JsonResource;

class LikeResource extends JsonResource
{
    public function toArray($request): array
    {

        /** @var Like $like */
        $like = $this->resource;

        /** @var LikeUser $user */
        $user = $like->user;
        return [
            'id' => $like->getIdValue(),
            'value' => $like->getValue(),
            'created_at' => $like->getCreatedAt(),
            'user' => $user ? [
                'id' => $user->getIdValue(),
                'uuid' => $user->getUuid()->value(),
                'name' => $user->getName()->value(),
            ] : null,
        ];
    }
}
