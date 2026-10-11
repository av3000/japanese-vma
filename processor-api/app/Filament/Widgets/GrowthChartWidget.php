<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Application\AdminDashboard\Interfaces\Readers\AdminDashboardReaderInterface;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class GrowthChartWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Weekly growth';

    protected ?string $description = 'Registrations and new articles per week for the last 12 weeks. Weeks start on Monday, UTC.';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $weeks = app(AdminDashboardReaderInterface::class)->weeklyGrowth()->weeks;

        return [
            'datasets' => [
                [
                    'label' => 'Registrations',
                    'data' => array_column($weeks, 'registrations'),
                    'borderColor' => '#d97706',
                    'backgroundColor' => '#d97706',
                ],
                [
                    'label' => 'Articles',
                    'data' => array_column($weeks, 'articles'),
                    'borderColor' => '#2563eb',
                    'backgroundColor' => '#2563eb',
                    'borderDash' => [6, 4],
                ],
            ],
            'labels' => array_map(
                static fn (array $week): string => CarbonImmutable::parse($week['week_start'])->format('j M'),
                $weeks,
            ),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
            ],
        ];
    }
}
