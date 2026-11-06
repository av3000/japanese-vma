<?php

declare(strict_types=1);

namespace App\Http\v1\Translations\Resources;

use App\Domain\Translations\Models\TranslationLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The languages a client may choose from. The list is small and unpaginated, so it carries no
 * `pagination` block; the `items` key matches the other v1 lists.
 *
 * @property list<TranslationLocale> $resource
 */
class TranslationLocaleListResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            /** @var array<int, TranslationLocaleResource> */
            'items' => TranslationLocaleResource::collection($this->resource)->resolve($request),
        ];
    }
}
