<?php

namespace App\Http\v1\Catalogues\Resources;

use App\Domain\Catalogues\DTOs\CatalogueLegacyIdentityDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property CatalogueLegacyIdentityDTO $resource
 */
class CatalogueLegacyIdentityResource extends JsonResource
{
    public static $wrap = null;

    /**
     * The typed return is what makes Scramble emit a named component schema
     * rather than an inline shape that can drift away from this Resource.
     *
     * Intentionally minimal: callers use this only to swap a legacy numeric id
     * for the UUID they then fetch the catalogue with.
     *
     * @return array{id: int, uuid: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'uuid' => $this->resource->uuid->value(),
        ];
    }
}
