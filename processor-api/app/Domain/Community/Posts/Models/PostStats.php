<?php

declare(strict_types=1);

namespace App\Domain\Community\Posts\Models;

/**
 * Aggregate engagement counts for one Post.
 *
 * Mirrors ArticleStats so Post reads can reuse EngagementStatsResource. Posts
 * have no download concept, so downloads are always zero.
 */
readonly class PostStats
{
    public function __construct(
        private int $likesCount = 0,
        private int $viewsCount = 0,
        private int $commentsCount = 0,
    ) {
    }

    public function getLikesCount(): int
    {
        return $this->likesCount;
    }

    public function getDownloadsCount(): int
    {
        return 0;
    }

    public function getViewsCount(): int
    {
        return $this->viewsCount;
    }

    public function getCommentsCount(): int
    {
        return $this->commentsCount;
    }
}
