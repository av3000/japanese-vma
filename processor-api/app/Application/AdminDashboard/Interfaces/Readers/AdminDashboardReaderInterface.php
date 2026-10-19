<?php

declare(strict_types=1);

namespace App\Application\AdminDashboard\Interfaces\Readers;

use App\Domain\AdminDashboard\DTOs\AttentionCountsDTO;
use App\Domain\AdminDashboard\DTOs\ContentStatsDTO;
use App\Domain\AdminDashboard\DTOs\ReviewQueueItemDTO;
use App\Domain\AdminDashboard\DTOs\WeeklyGrowthDTO;

/**
 * Aggregates behind the admin dashboard widgets. Each method is one grouped read, never a query
 * per status or per week.
 */
interface AdminDashboardReaderInterface
{
    public const GROWTH_WEEKS = 12;

    public const REVIEW_QUEUE_SIZE = 10;

    public function contentStats(): ContentStatsDTO;

    public function attentionCounts(): AttentionCountsDTO;

    public function weeklyGrowth(): WeeklyGrowthDTO;

    /**
     * The oldest articles awaiting review, oldest first.
     *
     * @return list<ReviewQueueItemDTO>
     */
    public function reviewQueue(): array;
}
