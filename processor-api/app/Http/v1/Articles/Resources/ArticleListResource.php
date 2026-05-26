<?php

namespace App\Http\v1\Articles\Resources;

use App\Domain\Articles\DTOs\ArticleListItemDTO;
use App\Domain\Articles\DTOs\ArticleListResultDTO;
use App\Http\v1\Shared\Resources\PaginationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ArticleListResultDTO $resource
 */
class ArticleListResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{
     *     items: array<int, ArticleResource>,
     *     pagination: PaginationResource
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var array<int, ArticleResource> $items */
        $items = array_map(
            fn(ArticleListItemDTO $item): ArticleResource => new ArticleResource(
                article: $item->article,
                options: [
                    'include_hashtags' => $this->resource->include_hashtags,
                    'include_stats' => $this->resource->include_stats,
                ],
                stats: $item->stats,
                hashtags: $item->hashtags,
                lastOperation: $item->lastOperation,
            ),
            $this->resource->items,
        );

        return [
            /** @var array<int, ArticleResource> */
            'items' => $items,
            'pagination' => new PaginationResource($this->resource->pagination),
        ];
    }
}
