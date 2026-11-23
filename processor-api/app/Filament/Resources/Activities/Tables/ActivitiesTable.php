<?php

declare(strict_types=1);

namespace App\Filament\Resources\Activities\Tables;

use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Activity\Enums\ActivitySource;
use App\Domain\Activity\Enums\ActivitySubjectType;
use App\Filament\Support\DateRangeFilter;
use App\Infrastructure\Persistence\Models\AdminActivity;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('actor_name')
                    ->label('Actor')
                    ->searchable(),
                TextColumn::make('action')
                    ->badge()
                    ->formatStateUsing(static fn (ActivityAction $state): string => $state->label()),
                TextColumn::make('subject_type')
                    ->label('Subject')
                    ->formatStateUsing(static fn (ActivitySubjectType $state): string => $state->label()),
                TextColumn::make('subject_label')
                    ->label('Subject name')
                    ->placeholder('-')
                    ->searchable()
                    ->limit(60),
                TextColumn::make('source')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(static fn (ActivitySource $state): string => $state->label()),
            ])
            ->filters([
                SelectFilter::make('actor_id')
                    ->label('Actor')
                    ->options(static fn (): array => AdminActivity::query()
                        ->whereNotNull('actor_id')
                        ->distinct()
                        ->orderBy('actor_name')
                        ->pluck('actor_name', 'actor_id')
                        ->all())
                    ->searchable(),
                SelectFilter::make('action')
                    ->options(self::labels(ActivityAction::cases())),
                SelectFilter::make('subject_type')
                    ->label('Subject')
                    ->options(self::labels(ActivitySubjectType::cases())),
                SelectFilter::make('source')
                    ->options(self::labels(ActivitySource::cases())),
                DateRangeFilter::make('created_at', 'Recorded'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    /**
     * @param list<ActivityAction|ActivitySubjectType|ActivitySource> $cases
     *
     * @return array<string, string>
     */
    private static function labels(array $cases): array
    {
        $options = [];

        foreach ($cases as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
