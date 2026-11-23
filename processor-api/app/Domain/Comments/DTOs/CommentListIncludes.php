<?php

declare(strict_types=1);

namespace App\Domain\Comments\DTOs;

final readonly class CommentListIncludes
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
        public bool $includeReplies = false,
        public int $repliesLimit = self::DEFAULT_REPLIES_LIMIT,
    ) {
    }

    /**
     * @param array<string, mixed> $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            includeReplies: (bool) ($validated['include_replies'] ?? false),
            repliesLimit: (int) ($validated['replies_limit'] ?? self::DEFAULT_REPLIES_LIMIT),
        );
    }

    /**
     * Subtree sizes are wanted whether or not previews are: a reader needs to
     * know a comment has forty replies before deciding to load them, and the
     * count costs the same query either way. Zero previews still returns counts.
     */
    public static function countsOnly(): self
    {
        return new self(includeReplies: false);
    }

    /**
     * How many previews to ask the reader for per root. Zero means counts only.
     */
    public function previewLimit(): int
    {
        return $this->includeReplies ? $this->repliesLimit : 0;
    }
}
