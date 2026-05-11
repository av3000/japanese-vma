<?php

namespace App\Http\v1\Catalogues\Resources;

use App\Domain\Catalogues\DTOs\CatalogueListItemDTO;
use App\Domain\Catalogues\DTOs\CatalogueListResultDTO;
use App\Http\v1\Shared\Resources\PaginationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property CatalogueListResultDTO $resource
 */
class CatalogueListResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{
     *     items: array<int, CatalogueResource>,
     *     pagination: PaginationResource
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'items' => array_map(
                static fn(CatalogueListItemDTO $item): CatalogueResource => new CatalogueResource(
                    $item->catalogue,
                    $item->stats,
                    $item->hashtags,
                    $item->itemsCount,
                ),
                $this->resource->items,
            ),
            'pagination' => new PaginationResource($this->resource->pagination),
        ];
    }
}
