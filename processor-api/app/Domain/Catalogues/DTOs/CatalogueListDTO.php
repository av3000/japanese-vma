<?php

namespace App\Domain\Catalogues\DTOs;

// TODO: Default values should not come from here.
readonly class CatalogueListDTO
{
    public function __construct(
        public ?string $search,
        public ?string $sort_by,
        public ?string $sort_dir,
        public ?int $per_page,
        public ?int $page,
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            search: $validated['search'] ?? null,
            sort_by: $validated['sort_by'] ?? null,
            sort_dir: $validated['sort_dir'] ?? null,
            per_page: $validated['per_page'] ?? null,
            page: $validated['page'] ?? null,
        );
    }
}
