<?php

declare(strict_types=1);

namespace App\Domain\Catalogues\DTOs;

use App\Domain\Catalogues\Support\CatalogueTagParser;
use App\Domain\Shared\Enums\SavedListType;

readonly class CatalogueCreateDTO
{
    public function __construct(
        public string $title,
        public ?string $description,
        public SavedListType $type,
        public bool $publicity,
        public array $hashtags = [],
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            title: $validated['title'],
            description: $validated['description'] ?? null,
            type: SavedListType::from((int) $validated['type']),
            publicity: (bool) ($validated['publicity'] ?? false),
            hashtags: CatalogueTagParser::parse($validated['tags'] ?? null),
        );
    }
}
