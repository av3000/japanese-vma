<?php

namespace App\Http\v1\Articles\Resources;

use App\Domain\Articles\DTOs\ArticleModerationItemDTO;
use App\Http\v1\Engagement\Resources\HashtagResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ArticleModerationItemDTO $resource
 */
class ArticleModerationItemResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{uuid: string, title_jp: string, status: int, status_label: string, hashtags: mixed, created_at: string}
     */
    public function toArray(Request $request): array
    {
        $article = $this->resource->article;

        return [
            'uuid' => $article->getUid()->value(),
            'title_jp' => $article->getTitleJp()->value,
            'status' => $article->getStatus()->value,
            'status_label' => $article->getStatus()->label(),
            'hashtags' => HashtagResource::collection($this->resource->hashtags),
            'created_at' => $article->getCreatedAt()->format('c'),
        ];
    }
}
