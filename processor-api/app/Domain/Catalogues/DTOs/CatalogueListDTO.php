<?php

namespace App\Domain\Catalogues\DTOs;

// TODO: Default values should not come from here.
readonly class CatalogueListDTO
{
    public function __construct(
        public ?string $search,
        public ?string $owner_uid,
        public ?int $type,
        public ?string $sort_by,
        public ?string $sort_dir,
        public ?int $per_page,
        public ?int $page,
        public bool $public_only = true,
        public bool $custom_only = true,
        public bool $include_stats_counts = true,
        public bool $include_hashtags = true,
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            search: $validated['search'] ?? null,
            owner_uid: $validated['owner_uid'] ?? null,
            type: $validated['type'] ?? null,
            sort_by: $validated['sort_by'] ?? null,
            sort_dir: $validated['sort_dir'] ?? null,
            per_page: $validated['per_page'] ?? null,
            page: $validated['page'] ?? null,
            public_only: $validated['public_only'] ?? true,
            custom_only: $validated['custom_only'] ?? true,
            include_stats_counts: $validated['include_stats_counts'] ?? true,
            include_hashtags: $validated['include_hashtags'] ?? true,
        );
    }
}
