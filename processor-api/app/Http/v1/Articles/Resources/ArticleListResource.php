<?php

namespace App\Http\v1\Articles\Resources;

use App\Domain\Articles\DTOs\ArticleFacetDTO;
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
     * `facets` is always present, empty when they were not requested, so clients get
     * one stable envelope instead of a key that appears and disappears.
     *
     * @return array{
     *     items: array<int, ArticleResource>,
     *     facets: array<int, ArticleFacetResource>,
     *     query: ArticleAppliedCriteriaResource,
     *     pagination: PaginationResource
     * }
     */
    public function toArray(Request $request): array
    {
        $includes = $this->resource->includes;

        /** @var array<int, ArticleResource> $items */
        $items = array_map(
            fn (ArticleListItemDTO $item): ArticleResource => new ArticleResource(
                article: $item->article,
                options: [
                    'include_hashtags' => $includes->includeHashtags,
                    'include_stats' => $includes->includeStats,
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
            'query' => new ArticleAppliedCriteriaResource($this->resource->query),
            'pagination' => new PaginationResource($this->resource->pagination->toArray()),
        ];
    }
}
