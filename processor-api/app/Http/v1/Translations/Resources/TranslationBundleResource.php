<?php

declare(strict_types=1);

namespace App\Http\v1\Translations\Resources;

use App\Domain\Translations\DTOs\TranslationBundleDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property TranslationBundleDTO $resource
 */
class TranslationBundleResource extends JsonResource
{
    public static $wrap = null;

    /**
     * `messages` is a flat map of dotted key to text, so it is typed `Record<string, string>`.
     *
     * @return array{locale: string, fallback_locale: string, messages: array<string, string>}
     */
    public function toArray(Request $request): array
    {
        return [
            'locale' => (string) $this->resource->locale,
            'fallback_locale' => (string) $this->resource->fallbackLocale,
            /** @var array<string, string> */
            'messages' => $this->resource->messages,
        ];
    }
}
