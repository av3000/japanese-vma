<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Readers;

use App\Application\AdminDashboard\Interfaces\Readers\AdminDashboardReaderInterface;
use App\Domain\AdminDashboard\DTOs\AttentionCountsDTO;
use App\Domain\AdminDashboard\DTOs\ContentStatsDTO;
use App\Domain\AdminDashboard\DTOs\ReviewQueueItemDTO;
use App\Domain\AdminDashboard\DTOs\WeeklyGrowthDTO;
use App\Domain\Processing\Enums\ProcessingStatus;
use App\Domain\Processing\ProcessingThresholds;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Infrastructure\Persistence\Models\ProcessingState;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Live dashboard aggregates. Counts across tables are scalar subqueries in one statement, and
 * per-status or per-week counts are one grouped query, so the cost does not grow with the
 * number of statuses or weeks. Week boundaries are ISO weeks in UTC (the app timezone).
 */
final class DatabaseAdminDashboardReader implements AdminDashboardReaderInterface
{
    public function contentStats(): ContentStatsDTO
    {
        $weekAgo = CarbonImmutable::now()->subDays(7);

        $totals = DB::selectOne(
            'SELECT
                (SELECT COUNT(*) FROM users) AS users,
                (SELECT COUNT(*) FROM users WHERE created_at >= ?) AS new_users,
                (SELECT COUNT(*) FROM customlists WHERE publicity = true) AS public_catalogues,
                (SELECT COUNT(*) FROM posts) AS posts,
                (SELECT COUNT(*) FROM comments WHERE created_at >= ?) AS new_comments',
            [$weekAgo, $weekAgo],
        );

        $articlesByStatus = array_fill_keys(array_map(
            static fn (ArticleStatus $status): int => $status->value,
            ArticleStatus::cases(),
        ), 0);

        $grouped = DB::table('articles')
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        foreach ($grouped as $status => $total) {
            $articlesByStatus[(int) $status] = (int) $total;
        }

        return new ContentStatsDTO(
            users: (int) $totals->users,
            newUsersLastWeek: (int) $totals->new_users,
            articlesByStatus: $articlesByStatus,
            publicCatalogues: (int) $totals->public_catalogues,
            posts: (int) $totals->posts,
            newCommentsLastWeek: (int) $totals->new_comments,
        );
    }

    public function attentionCounts(): AttentionCountsDTO
    {
        $now = CarbonImmutable::now();

        // One statement of scalar subqueries; "stuck" is the shared ProcessingState::stale() rule.
        $counts = DB::query()
            ->selectSub(DB::table('articles')->selectRaw('COUNT(*)')->whereIn('status', ArticleStatus::awaitingReviewValues()), 'awaiting_review')
            ->selectSub(DB::table('processing_states')
                ->selectRaw('COUNT(*)')
                ->where('status', ProcessingStatus::FAILED->value)
                ->whereRaw('COALESCE(finished_at, updated_at) >= ?', [$now->subDay()]), 'failed_last_day')
            ->selectSub(ProcessingState::query()->stale(ProcessingThresholds::staleBefore($now))->selectRaw('COUNT(*)')->toBase(), 'stuck')
            ->selectSub(DB::table('failed_jobs')->selectRaw('COUNT(*)'), 'failed_jobs')
            ->first();

        return new AttentionCountsDTO(
            articlesAwaitingReview: (int) $counts->awaiting_review,
            processingFailedLastDay: (int) $counts->failed_last_day,
            processingStuck: (int) $counts->stuck,
            failedJobs: (int) $counts->failed_jobs,
        );
    }

    public function weeklyGrowth(): WeeklyGrowthDTO
    {
        $firstWeek = CarbonImmutable::now()->startOfWeek()->subWeeks(self::GROWTH_WEEKS - 1);

        $weeks = [];
        for ($index = 0; $index < self::GROWTH_WEEKS; $index++) {
            $weekStart = $firstWeek->addWeeks($index)->toDateString();
            $weeks[$weekStart] = ['week_start' => $weekStart, 'registrations' => 0, 'articles' => 0];
        }

        foreach (['users' => 'registrations', 'articles' => 'articles'] as $table => $series) {
            $rows = DB::table($table)
                ->selectRaw("to_char(date_trunc('week', created_at), 'YYYY-MM-DD') AS week_start, COUNT(*) AS total")
                ->where('created_at', '>=', $firstWeek)
                ->groupByRaw("date_trunc('week', created_at)")
                ->get();

            foreach ($rows as $row) {
                if (isset($weeks[$row->week_start])) {
                    $weeks[$row->week_start][$series] = (int) $row->total;
                }
            }
        }

        return new WeeklyGrowthDTO(array_values($weeks));
    }

    public function reviewQueue(): array
    {
        return DB::table('articles')
            ->leftJoin('users', 'users.id', '=', 'articles.user_id')
            ->whereIn('articles.status', ArticleStatus::awaitingReviewValues())
            ->orderBy('articles.created_at')
            ->orderBy('articles.id')
            ->limit(self::REVIEW_QUEUE_SIZE)
            ->get([
                'articles.uuid',
                'articles.title_jp',
                'articles.title_en',
                'articles.status',
                'articles.created_at',
                'users.name AS author_name',
            ])
            ->map(static fn (object $row): ReviewQueueItemDTO => new ReviewQueueItemDTO(
                uuid: (string) $row->uuid,
                title: (string) ($row->title_jp ?: $row->title_en),
                authorName: (string) ($row->author_name ?? 'Deleted user'),
                status: (int) $row->status,
                createdAt: CarbonImmutable::parse($row->created_at)->toAtomString(),
            ))
            ->values()
            ->all();
    }
}
