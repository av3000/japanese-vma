<?php

namespace App\Http\v1\Catalogues\Resources;

use App\Domain\Catalogues\DTOs\CataloguePickerItemDTO;
use App\Domain\Catalogues\DTOs\CataloguePickerResultDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property CataloguePickerResultDTO $resource
 */
class CatalogueListForItemResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{
     *     items: array<int, CatalogueForItemResource>
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'items' => array_map(
                static fn (CataloguePickerItemDTO $item): CatalogueForItemResource => new CatalogueForItemResource($item),
                $this->resource->items,
            ),
        ];
    }
}
