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
     *     items: array<int, ArticleModerationItemResource>,
     *     pagination: PaginationResource
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var array<int, ArticleModerationItemResource> $items */
        $items = array_map(
            static fn (ArticleModerationItemDTO $item): ArticleModerationItemResource => new ArticleModerationItemResource($item),
            $this->resource->items,
        );

        return [
            /** @var array<int, ArticleModerationItemResource> */
            'items' => $items,
            'pagination' => new PaginationResource($this->resource->pagination),
        ];
    }
}
