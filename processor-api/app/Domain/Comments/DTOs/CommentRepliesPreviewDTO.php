<?php

declare(strict_types=1);

namespace App\Domain\Comments\DTOs;

use App\Domain\Comments\Models\Comment;

/**
 * One root comment's subtree: how big it is, and the slice that was loaded.
 *
 * `count` is the whole subtree at every depth and is independent of how many
 * replies were actually loaded: a caller that asked for three of forty still
 * gets forty here, because that is the number a "show all replies" control
 * renders. `replies` is empty when the caller asked for counts only.
 */
final readonly class CommentRepliesPreviewDTO
{
    /**
     * @param array<int, Comment> $replies oldest first
     */
    public function __construct(
        public int $count,
        public array $replies,
    ) {
    }

    public static function empty(): self
    {
        return new self(count: 0, replies: []);
    }
}
