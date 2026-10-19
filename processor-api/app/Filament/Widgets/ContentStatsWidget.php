<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Application\AdminDashboard\Interfaces\Readers\AdminDashboardReaderInterface;
use App\Domain\Shared\Enums\ArticleStatus;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContentStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Content';

    protected ?string $description = 'Cached for five minutes. "Last 7 days" counts back from now.';

    protected function getStats(): array
    {
        $stats = app(AdminDashboardReaderInterface::class)->contentStats();

        return [
            Stat::make('Users', number_format($stats->users))
                ->description(number_format($stats->newUsersLastWeek).' new in the last 7 days'),
            Stat::make('Articles', number_format($stats->articles()))
                ->description(sprintf(
                    '%s approved, %s awaiting review, %s rejected',
                    number_format($stats->articlesWithStatus(ArticleStatus::APPROVED)),
                    number_format(array_sum(array_map($stats->articlesWithStatus(...), ArticleStatus::awaitingReview()))),
                    number_format($stats->articlesWithStatus(ArticleStatus::REJECTED)),
                )),
            Stat::make('Public catalogues', number_format($stats->publicCatalogues)),
            Stat::make('Community posts', number_format($stats->posts)),
            Stat::make('Comments, last 7 days', number_format($stats->newCommentsLastWeek)),
        ];
    }

    protected function getColumns(): int
    {
        return 5;
    }
}
