<?php

namespace App\Http\v1\Articles\Resources;

use App\Application\Articles\DTOs\ArticleFacetDTO;
use App\Application\Articles\DTOs\ArticleFacetValueDTO;
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
     *     values: array<int, array{key: string, label: string, count: int, selected: bool}>
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->resource->key,
            'label' => $this->resource->label,
            'type' => $this->resource->type,
            'values' => array_map(
                static fn (ArticleFacetValueDTO $value): array => [
                    'key' => $value->key,
                    'label' => $value->label,
                    'count' => $value->count,
                    'selected' => $value->selected,
                ],
                $this->resource->values,
            ),
        ];
    }
}
