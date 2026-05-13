<?php

namespace App\Http\v1\Catalogues\Resources;

use App\Domain\Catalogues\DTOs\CataloguePickerItemDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property CataloguePickerItemDTO $resource
 */
class CatalogueForItemResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(CataloguePickerItemDTO $item)
    {
        parent::__construct($item);
    }

    /**
     * @return array{
     *     id: int,
     *     uuid: string,
     *     title: string,
     *     type: int,
     *     type_label: string,
     *     publicity: int,
     *     contains_item: bool
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var CataloguePickerItemDTO $item */
        $item = $this->resource;
        $catalogue = $item->catalogue;

        return [
            'id' => $catalogue->getIdValue(),
            'uuid' => (string) $catalogue->getUid(),
            'title' => (string) $catalogue->getTitle(),
            'type' => $catalogue->getType()->value,
            'type_label' => $catalogue->getTypeLabel(),
            'publicity' => $catalogue->getPublicity()->value,
            'contains_item' => $item->containsItem,
        ];
    }
}
