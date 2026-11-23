<?php

declare(strict_types=1);

namespace App\Http\v1\Catalogues\Resources;

use App\Domain\Catalogues\DTOs\ViewerCatalogueStateDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Whether the signed-in viewer has saved an item to one of their lists, and whether it is in their
 * "known" list. One named schema for every resource that carries `viewer_catalogue_state`, so the
 * generated client has a single type instead of one inline copy per resource.
 *
 * @property ViewerCatalogueStateDTO $resource
 */
class ViewerCatalogueStateResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(ViewerCatalogueStateDTO $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array{is_saved: bool, is_known: bool|null}
     */
    public function toArray(Request $request): array
    {
        /** @var ViewerCatalogueStateDTO $state */
        $state = $this->resource;

        return [
            'is_saved' => (bool) $state->isSaved,
            'is_known' => $state->isKnown === null ? null : (bool) $state->isKnown,
        ];
    }
}
