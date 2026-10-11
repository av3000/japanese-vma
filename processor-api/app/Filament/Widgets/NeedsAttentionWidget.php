<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Application\AdminDashboard\Interfaces\Readers\AdminDashboardReaderInterface;
use App\Domain\Processing\ProcessingThresholds;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Processing\ProcessingStateResource;
use App\Filament\Support\PanelActor;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class NeedsAttentionWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Needs attention';

    public static function canView(): bool
    {
        return Filament::auth()->check()
            && (PanelActor::may('articles.view') || PanelActor::may('operations.view'));
    }

    protected function getDescription(): string
    {
        return sprintf(
            'Processing counts as stuck after %d minutes without progress. Failures cover the last 24 hours.',
            intdiv(ProcessingThresholds::STALE_AFTER_SECONDS, 60),
        );
    }

    protected function getStats(): array
    {
        $counts = app(AdminDashboardReaderInterface::class)->attentionCounts();

        $processing = ProcessingStateResource::canViewAny()
            ? ProcessingStateResource::getUrl('index', ['tab' => 'needs-attention'])
            : null;

        return [
            $this->count('Articles awaiting review', $counts->articlesAwaitingReview, 'warning', Heroicon::OutlinedInboxStack)
                ->url(ArticleResource::canViewAny() ? ArticleResource::getUrl('index') : null),
            $this->count('Processing failed, 24 h', $counts->processingFailedLastDay, 'danger', Heroicon::OutlinedExclamationTriangle)
                ->url($processing),
            $this->count('Processing stuck', $counts->processingStuck, 'danger', Heroicon::OutlinedClock)
                ->url($processing),
            $this->count('Failed queue jobs', $counts->failedJobs, 'danger', Heroicon::OutlinedQueueList),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }

    /**
     * A non-zero count gets a colour and an icon, so the state reads without relying on colour.
     */
    private function count(string $label, int $value, string $color, Heroicon $icon): Stat
    {
        $stat = Stat::make($label, number_format($value));

        if ($value === 0) {
            return $stat->description('Nothing to do')->descriptionIcon(Heroicon::OutlinedCheckCircle)->color('success');
        }

        return $stat->description('Needs a look')->descriptionIcon($icon)->color($color);
    }
}
