<?php

declare(strict_types=1);

namespace App\Filament\Resources\Processing;

use App\Domain\Processing\ProcessingThresholds;
use App\Filament\AdminNavigationGroup;
use App\Filament\Resources\Processing\Pages\ListProcessingStates;
use App\Filament\Resources\Processing\Pages\ViewProcessingState;
use App\Filament\Resources\Processing\Schemas\ProcessingStateInfolist;
use App\Filament\Resources\Processing\Tables\ProcessingStatesTable;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\ProcessingState;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Article content processing, one row per article and task (processing_states, ADR 0001), so a
 * failed or stuck extraction is visible to operators and not only as a banner to the author.
 */
class ProcessingStateResource extends Resource
{
    protected static ?string $model = ProcessingState::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Operations;

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Processing';

    protected static ?string $modelLabel = 'processing run';

    protected static ?string $slug = 'operations/processing';

    public const RETRY_LIMIT = 50;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->select('processing_states.*')
            ->addSelect([
                'article_title' => Article::query()
                    ->select('title_jp')
                    ->whereColumn('articles.uuid', 'processing_states.entity_id')
                    ->limit(1),
            ]);
    }

    /**
     * Pending or processing without progress for longer than the sweeper's threshold.
     */
    public static function scopeStuck(Builder $query): Builder
    {
        return $query->stale(ProcessingThresholds::staleBefore(CarbonImmutable::now()));
    }

    public static function isStuck(ProcessingState $state): bool
    {
        return ProcessingThresholds::isStale($state->status, $state->updated_at, $state->created_at, CarbonImmutable::now());
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProcessingStateInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProcessingStatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProcessingStates::route('/'),
            'view' => ViewProcessingState::route('/{record}'),
        ];
    }
}
