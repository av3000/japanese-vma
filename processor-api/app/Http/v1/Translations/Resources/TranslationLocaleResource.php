<?php

declare(strict_types=1);

namespace App\Http\v1\Translations\Resources;

use App\Domain\Translations\Models\TranslationLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property TranslationLocale $resource
 */
class TranslationLocaleResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{code: string, name: string, is_fallback: bool}
     */
    public function toArray(Request $request): array
    {
        return [
            'code' => (string) $this->resource->getCode(),
            'name' => (string) $this->resource->getName(),
            'is_fallback' => (bool) $this->resource->isFallback(),
        ];
    }
}
