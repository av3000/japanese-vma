<?php

declare(strict_types=1);

namespace App\Filament\Resources\Processing\Pages;

use App\Domain\Processing\Enums\ProcessingStatus;
use App\Domain\Processing\ProcessingThresholds;
use App\Filament\Resources\Processing\ProcessingStateResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProcessingStates extends ListRecords
{
    protected static string $resource = ProcessingStateResource::class;

    public function getSubheading(): string
    {
        return sprintf(
            'A run counts as stuck after %d minutes without progress, the same threshold the sweeper uses.',
            intdiv(ProcessingThresholds::STALE_AFTER_SECONDS, 60),
        );
    }

    /**
     * "Needs attention" is the saved preset: failed runs plus runs stuck in progress.
     */
    public function getTabs(): array
    {
        return [
            'needs-attention' => Tab::make('Needs attention')
                ->modifyQueryUsing(static fn (Builder $query): Builder => $query->where(static fn (Builder $inner): Builder => $inner
                    ->where('processing_states.status', ProcessingStatus::FAILED->value)
                    ->orWhere(static fn (Builder $stuck): Builder => ProcessingStateResource::scopeStuck($stuck)))),
            'in-progress' => Tab::make('In progress')
                ->modifyQueryUsing(static fn (Builder $query): Builder => $query->whereIn('processing_states.status', ProcessingStatus::nonTerminalValues())),
            'all' => Tab::make('All'),
        ];
    }
}
