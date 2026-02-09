<?php

namespace App\Domain\Comments\DTOs;

readonly class CommentListDTO
{
    public function __construct(
        public ?string $search,
        public ?string $sort_by,
        public ?string $sort_dir,
        public ?int $per_page,
        public ?int $page,
        public bool $include_replies = false,
        public bool $include_likes = false,
        public bool $include_author = true,
        public bool $include_engagement_summary = true,

    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            search: $validated['search'] ?? null,
            sort_by: $validated['sort_by'] ?? 'created_at',
            sort_dir: $validated['sort_dir'] ?? 'desc',
            per_page: $validated['per_page'] ?? null,
            page: $validated['page'] ?? null,
            include_replies: $validated['include_replies'] ?? false,
            include_likes: $validated['include_likes'] ?? false,
            include_author: $validated['include_author'] ?? true,
            include_engagement_summary: $validated['include_engagement_summary'] ?? true,
        );
    }
}
