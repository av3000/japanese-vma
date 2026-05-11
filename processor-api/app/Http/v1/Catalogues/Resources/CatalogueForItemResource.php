<?php

namespace App\Http\v1\Catalogues\Resources;

use App\Domain\Catalogues\Models\Catalogue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Catalogue $resource
 */
class CatalogueForItemResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(
        Catalogue $catalogue,
        private readonly bool $containsItem
    ) {
        parent::__construct($catalogue);
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
        /** @var Catalogue $catalogue */
        $catalogue = $this->resource;

        return [
            'id' => $catalogue->getIdValue(),
            'uuid' => (string) $catalogue->getUid(),
            'title' => (string) $catalogue->getTitle(),
            'type' => $catalogue->getType()->value,
            'type_label' => $catalogue->getTypeLabel(),
            'publicity' => $catalogue->getPublicity()->value,
            'contains_item' => $this->containsItem,
        ];
    }
}
