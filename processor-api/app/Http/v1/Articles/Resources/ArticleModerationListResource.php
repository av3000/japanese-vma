<?php

namespace App\Http\v1\Articles\Resources;

use App\Domain\Articles\DTOs\ArticleModerationItemDTO;
use App\Domain\Articles\DTOs\ArticleModerationListResultDTO;
use App\Http\v1\Shared\Resources\PaginationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ArticleModerationListResultDTO $resource
 */
class ArticleModerationListResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{
     *     items: array<int, array{
     *         uuid: string,
     *         title_jp: string,
     *         status: int,
     *         status_label: string,
     *         hashtags: array<int, HashtagResource>,
     *         created_at: string
     *     }>,
     *     pagination: PaginationResource
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'items' => array_map(
                static fn (ArticleModerationItemDTO $item): ArticleModerationItemResource => new ArticleModerationItemResource($item),
                $this->resource->items,
            ),
            'pagination' => new PaginationResource($this->resource->pagination),
        ];
    }
}
