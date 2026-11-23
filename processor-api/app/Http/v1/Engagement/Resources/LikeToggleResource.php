<?php

declare(strict_types=1);

namespace App\Http\v1\Engagement\Resources;

use App\Domain\Engagement\DTOs\LikeToggleResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The generic like toggle response.
 *
 * Unwrapped, and the casts below are explicit: the previous shape
 * (`{success, like, likeValues}` inside a TypedResults envelope) generated as
 * `"type": "string"` in api.json, so callers had no typed model to bind to.
 *
 * @property-read LikeToggleResult $resource
 */
class LikeToggleResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(LikeToggleResult $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array{is_liked: bool, likes_count: int}
     */
    public function toArray(Request $request): array
    {
        /** @var LikeToggleResult $result */
        $result = $this->resource;

        return [
            'is_liked' => (bool) $result->isLiked,
            'likes_count' => (int) $result->likesCount,
        ];
    }
}
