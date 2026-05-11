<?php

namespace App\Http\v1\Catalogues\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{
 *     items: array<int, array{
 *         id: int,
 *         uuid: string,
 *         title: string,
 *         type: int,
 *         type_label: string,
 *         publicity: int,
 *         contains_item: bool
 *     }>
 * } $resource
 */
class CatalogueForItemListResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{
     *     items: array<int, array{
     *         id: int,
     *         uuid: string,
     *         title: string,
     *         type: int,
     *         type_label: string,
     *         publicity: int,
     *         contains_item: bool
     *     }>
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'items' => $this->resource['items'],
        ];
    }
}
