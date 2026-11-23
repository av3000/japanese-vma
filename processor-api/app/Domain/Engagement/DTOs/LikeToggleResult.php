<?php

declare(strict_types=1);

namespace App\Domain\Engagement\DTOs;

/**
 * The whole outcome of a toggle: the viewer's new state and the target's new total.
 * Both fields are needed because the caller re-renders a like control from the
 * response alone, without a follow-up read.
 */
readonly class LikeToggleResult
{
    public function __construct(
        public bool $isLiked,
        public int $likesCount,
    ) {
    }
}
