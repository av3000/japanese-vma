<?php
namespace App\Domain\Articles\DTOs;

readonly class ArticleListDTO
{
    public function __construct(
        public ?int $category,
        public ?string $search,
        public ?string $sort_by,
        public ?string $sort_dir,
        public ?int $per_page,
        public bool $includeStats = false,
        public bool $includeHashtags = true
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            category: $validated['category'] ?? null,
            search: $validated['search'] ?? null,
            sort_by: $validated['sort_by'] ?? null,
            sort_dir: $validated['sort_dir'] ?? null,
            per_page: $validated['per_page'] ?? null,
            includeStats: $validated['include_stats'] ?? false,
            includeHashtags: $validated['include_hashtags'] ?? true
        );
    }
}
