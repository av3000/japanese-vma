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
        public ?int $page,
        public bool $include_stats_counts = false,
        public bool $include_engagement_data = false,
        public bool $include_hashtags = true
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            category: $validated['category'] ?? null,
            search: $validated['search'] ?? null,
            sort_by: $validated['sort_by'] ?? null,
            sort_dir: $validated['sort_dir'] ?? null,
            per_page: $validated['per_page'] ?? null,
            page: $validated['page'] ?? null,
            include_stats_counts: $validated['include_stats'] ?? true,
            include_engagement_data: $validated['include_stats_counts'] ?? false,
            include_hashtags: $validated['include_hashtags'] ?? true
        );
    }
}
