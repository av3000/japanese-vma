<?php

declare(strict_types=1);

namespace App\Domain\Comments\DTOs;

use App\Domain\Comments\Models\Comment;

/**
 * One root comment together with the thread hanging off it.
 *
 * The Comment itself is built complete from its own row and knows nothing about
 * replies; how many there are and which were loaded are facts about a query
 * result, so they live here instead. That is what keeps a Comment read by
 * identity from silently reporting `replies_count: 0` for a busy thread.
 *
 * Also used for single-comment write responses, where the count is real and the
 * previews are empty.
 */
final readonly class CommentListItemDTO
{
    /**
     * @param array<int, Comment> $replyPreviews oldest first, empty when previews were not requested
     */
    public function __construct(
        public Comment $comment,
        public int $repliesCount,
        public array $replyPreviews,
    ) {
    }

    public static function fromPreview(Comment $comment, CommentRepliesPreviewDTO $preview): self
    {
        return new self(
            comment: $comment,
            repliesCount: $preview->count,
            replyPreviews: $preview->replies,
        );
    }
}
