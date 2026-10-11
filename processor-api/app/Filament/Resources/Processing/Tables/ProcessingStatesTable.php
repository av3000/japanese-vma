<?php

declare(strict_types=1);

namespace App\Filament\Resources\Processing\Tables;

use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\Enums\ProcessingStatus;
use App\Domain\Processing\Enums\ProcessingTaskType;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Processing\Actions\RetryProcessingActions;
use App\Filament\Resources\Processing\ProcessingStateResource;
use App\Filament\Support\DateRangeFilter;
use App\Filament\Support\StatusBadges;
use App\Infrastructure\Persistence\Models\ProcessingState;
use Carbon\CarbonImmutable;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProcessingStatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('entity_type')
                    ->label('Entity')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(static fn (ProcessingEntityType $state): string => ucfirst($state->value)),
                TextColumn::make('article_title')
                    ->label('Article')
                    ->placeholder('Deleted article')
                    ->limit(40)
                    ->url(static fn (ProcessingState $record): ?string => $record->getAttribute('article_title') !== null && ArticleResource::canViewAny()
                        ? ArticleResource::getUrl('view', ['record' => $record->entity_id])
                        : null),
                TextColumn::make('task_type')
                    ->label('Task')
                    ->formatStateUsing(static fn (string $state): string => self::taskLabel($state))
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->state(static fn (ProcessingState $record): string => ProcessingStateResource::isStuck($record) ? 'stuck' : $record->status->value)
                    ->formatStateUsing(static fn (string $state): string => ucfirst($state))
                    ->color(static fn (string $state): string => $state === 'stuck' ? 'danger' : StatusBadges::processingColor(ProcessingStatus::tryFrom($state)))
                    ->icon(static fn (string $state): ?string => $state === 'stuck' ? 'heroicon-o-clock' : null),
                TextColumn::make('attempt')
                    ->label('Attempt')
                    ->formatStateUsing(static fn (ProcessingState $record): string => "{$record->attempt} of {$record->max_attempts}"),
                TextColumn::make('duration')
                    ->label('Duration')
                    ->state(static fn (ProcessingState $record): ?string => self::duration($record))
                    ->placeholder('-'),
                TextColumn::make('error_message')
                    ->label('Error')
                    ->state(static fn (ProcessingState $record): ?string => $record->error_message === null
                        ? null
                        : trim(($record->error_code ?? '').' '.$record->error_message))
                    ->limit(60)
                    ->tooltip(static fn (ProcessingState $record): ?string => $record->error_message)
                    ->placeholder('-')
                    ->wrap(),
                TextColumn::make('started_at')->dateTime()->sortable()->toggleable(),
                TextColumn::make('updated_at')->label('Last progress')->since()->dateTimeTooltip()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->multiple()
                    ->options(array_combine(
                        array_map(static fn (ProcessingStatus $status): string => $status->value, ProcessingStatus::cases()),
                        array_map(static fn (ProcessingStatus $status): string => ucfirst($status->value), ProcessingStatus::cases()),
                    )),
                SelectFilter::make('task_type')
                    ->label('Task')
                    ->options(array_combine(
                        array_map(static fn (ProcessingTaskType $task): string => $task->value, ProcessingTaskType::cases()),
                        array_map(static fn (ProcessingTaskType $task): string => self::taskLabel($task->value), ProcessingTaskType::cases()),
                    )),
                SelectFilter::make('entity_type')
                    ->label('Entity')
                    ->options(array_combine(
                        array_map(static fn (ProcessingEntityType $type): string => $type->value, ProcessingEntityType::cases()),
                        array_map(static fn (ProcessingEntityType $type): string => ucfirst($type->value), ProcessingEntityType::cases()),
                    )),
                Filter::make('stuck')
                    ->label('Stuck in progress')
                    ->toggle()
                    ->query(static fn (Builder $query): Builder => ProcessingStateResource::scopeStuck($query)),
                DateRangeFilter::make('updated_at', 'Last progress'),
            ])
            ->recordActions([
                ViewAction::make(),
                RetryProcessingActions::retry(),
            ])
            ->toolbarActions([
                RetryProcessingActions::bulkRetry(),
            ]);
    }

    public static function taskLabel(string $task): string
    {
        return match (ProcessingTaskType::tryFrom($task)) {
            ProcessingTaskType::ArticleContentProcessing => 'Kanji and words',
            ProcessingTaskType::LegacyKanjiExtraction => 'Kanji (legacy)',
            ProcessingTaskType::LegacyWordsExtraction => 'Words (legacy)',
            null => $task,
        };
    }

    /**
     * From start to finish, or to now while still running. Queue wait is not recorded (the row is
     * reset at dispatch and started by the worker), so this is run time only.
     */
    public static function duration(ProcessingState $record): ?string
    {
        if ($record->started_at === null) {
            return null;
        }

        $end = $record->finished_at ?? ($record->status->isTerminal() ? null : CarbonImmutable::now());

        if ($end === null) {
            return null;
        }

        $seconds = max(0, $record->started_at->diffInSeconds($end));

        return $seconds < 60 ? (int) $seconds.' s' : sprintf('%d min %d s', intdiv((int) $seconds, 60), (int) $seconds % 60);
    }
}
