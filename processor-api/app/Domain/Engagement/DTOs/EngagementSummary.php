<?php

namespace App\Domain\Engagement\DTOs;

readonly class EngagementSummary
{
    public function __construct(
        public int $likesCount,
        public int $viewsCount,
        public int $downloadsCount,
        public bool $isLikedByViewer,
    ) {}

    public static function empty(): self
    {
        return new self(0, 0, 0, false);
    }
}
