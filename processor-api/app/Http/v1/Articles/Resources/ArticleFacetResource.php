<?php

namespace App\Http\v1\Articles\Resources;

use App\Domain\Articles\DTOs\ArticleFacetDTO;
use App\Domain\Articles\DTOs\ArticleFacetValueDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ArticleFacetDTO $resource
 */
class ArticleFacetResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     type: string,
     *     values: array<int, ArticleFacetValueResource>
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var array<int, ArticleFacetValueResource> $values */
        $values = array_map(
            static fn (ArticleFacetValueDTO $value): ArticleFacetValueResource => new ArticleFacetValueResource($value),
            $this->resource->values,
        );

        return [
            'key' => $this->resource->key,
            'label' => $this->resource->label,
            'type' => $this->resource->type,
            /** @var array<int, ArticleFacetValueResource> */
            'values' => $values,
        ];
    }
}
