<?php

declare(strict_types=1);

namespace App\Domain\Catalogues\DTOs;

use App\Domain\Catalogues\Support\CatalogueTagParser;
use App\Domain\Shared\Enums\SavedListType;

readonly class CatalogueUpdateDTO
{
    public function __construct(
        public ?string $title = null,
        public ?SavedListType $type = null,
        public ?bool $publicity = null,
        public ?array $hashtags = null,
        public bool $hashtagsPresent = false,
    ) {}

    public static function fromRequest(array $validated): self
    {
        $hashtagsPresent = array_key_exists('tags', $validated);

        return new self(
            title: $validated['title'] ?? null,
            type: array_key_exists('type', $validated)
                ? SavedListType::from((int) $validated['type'])
                : null,
            publicity: array_key_exists('publicity', $validated)
                ? (bool) $validated['publicity']
                : null,
            hashtags: $hashtagsPresent
                ? CatalogueTagParser::parse($validated['tags'])
                : null,
            hashtagsPresent: $hashtagsPresent,
        );
    }
}
