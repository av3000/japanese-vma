<?php

namespace App\Domain\Articles\DTOs;

class ArticleListDTO
{
    public function __construct(
        public ?int $category = null,
        public ?string $search = null,
        public string $sortBy = 'created_at',
        public string $sortDir = 'desc',
        public int $perPage = 4
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            category: $validated['category'] ?? null,
            search: $validated['search'] ?? null,
            sortBy: $validated['sort_by'] ?? 'created_at',
            sortDir: strtolower($validated['sort_dir'] ?? 'desc'),
            perPage: $validated['per_page'] ?? 4
        );
    }
}
