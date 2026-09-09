<?php

namespace App\Domain\Comments\DTOs;

readonly class CommentListDTO
{
    /**
     * How many replies ride along with each top-level comment by default.
     *
     * Small on purpose: the thread view shows a preview and `replies_count`,
     * and `GET /comments/{uuid}/replies` serves the rest on demand. Inlining a
     * whole subtree would make the response size a function of the busiest
     * comment on the page.
     */
    public const DEFAULT_REPLIES_LIMIT = 3;

    public const MAX_REPLIES_LIMIT = 50;

    public function __construct(
        public string $sort_by,
        public string $sort_dir,
        public ?int $per_page,
        public ?int $page,
        public bool $include_replies = false,
        public int $replies_limit = self::DEFAULT_REPLIES_LIMIT,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromRequest(array $validated): self
    {
        return new self(
            sort_by: $validated['sort_by'] ?? 'created_at',
            sort_dir: $validated['sort_dir'] ?? 'desc',
            per_page: $validated['per_page'] ?? null,
            page: $validated['page'] ?? null,
            include_replies: $validated['include_replies'] ?? false,
            replies_limit: $validated['replies_limit'] ?? self::DEFAULT_REPLIES_LIMIT,
        );
    }
}
