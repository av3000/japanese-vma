<?php

namespace App\Http\v1\Articles\Resources;

use App\Application\Articles\DTOs\ArticleFacetDTO;
use App\Application\Articles\DTOs\ArticleListItemDTO;
use App\Application\Articles\DTOs\ArticleListPageDTO;
use App\Http\v1\Shared\Resources\PaginationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ArticleListPageDTO $resource
 */
class ArticleListResource extends JsonResource
{
    public static $wrap = null;

    /**
     * `facets` is always present, empty when they were not requested, so clients get
     * one stable envelope instead of a key that appears and disappears.
     *
     * @return array{
     *     items: array<int, ArticleResource>,
     *     facets: array<int, ArticleFacetResource>,
     *     query: ArticleQueryEchoResource,
     *     pagination: PaginationResource
     * }
     */
    public function toArray(Request $request): array
    {
        $projection = $this->resource->projection;

        /** @var array<int, ArticleResource> $items */
        $items = array_map(
            fn (ArticleListItemDTO $item): ArticleResource => new ArticleResource(
                article: $item->article,
                options: [
                    'include_hashtags' => $projection->includeHashtags,
                    'include_stats' => $projection->includeStats,
                ],
                stats: $item->stats,
                hashtags: $item->hashtags,
                processingState: $item->processingState,
            ),
            $this->resource->items,
        );

        /** @var array<int, ArticleFacetResource> $facets */
        $facets = array_map(
            static fn (ArticleFacetDTO $facet): ArticleFacetResource => new ArticleFacetResource($facet),
            $this->resource->facets,
        );

        return [
            /** @var array<int, ArticleResource> */
            'items' => $items,
            /** @var array<int, ArticleFacetResource> */
            'facets' => $facets,
            'query' => new ArticleQueryEchoResource($this->resource->query),
            'pagination' => new PaginationResource($this->resource->pagination->toArray()),
        ];
    }
}
