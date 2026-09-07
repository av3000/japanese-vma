<?php

namespace App\Http\v1\Articles\Resources;

use App\Application\Articles\DTOs\ArticleFacetValueDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One choice inside a facet dimension.
 *
 * A dedicated resource rather than an inline array shape: Scramble does not infer
 * nested array{...} shapes inside an array, so the generated client typed facet
 * values as `unknown` and the frontend could not read a count without casting.
 *
 * @property ArticleFacetValueDTO $resource
 */
class ArticleFacetValueResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{key: string, label: string, count: int, selected: bool}
     */
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->resource->key,
            'label' => $this->resource->label,
            'count' => $this->resource->count,
            'selected' => $this->resource->selected,
        ];
    }
}
