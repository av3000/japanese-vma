<?php

declare(strict_types=1);

namespace App\Filament\Resources\Processing\Schemas;

use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Processing\ProcessingStateResource;
use App\Filament\Resources\Processing\Tables\ProcessingStatesTable;
use App\Infrastructure\Persistence\Models\ProcessingState;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProcessingStateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Run')
                    ->schema([
                        TextEntry::make('article_title')
                            ->label('Article')
                            ->placeholder('Deleted article')
                            ->url(static fn (ProcessingState $record): ?string => $record->getAttribute('article_title') !== null && ArticleResource::canViewAny()
                                ? ArticleResource::getUrl('view', ['record' => $record->entity_id])
                                : null),
                        TextEntry::make('entity_type')->label('Entity')->formatStateUsing(static fn (ProcessingEntityType $state): string => ucfirst($state->value)),
                        TextEntry::make('entity_id')->label('Entity uuid')->copyable(),
                        TextEntry::make('task_type')->label('Task')->formatStateUsing(static fn (string $state): string => ProcessingStatesTable::taskLabel($state)),
                        TextEntry::make('status_label')
                            ->label('Status')
                            ->badge()
                            ->state(static fn (ProcessingState $record): string => ProcessingStateResource::isStuck($record) ? 'Stuck' : ucfirst($record->status->value))
                            ->color(static fn (string $state): string => match ($state) {
                                'Stuck', 'Failed' => 'danger',
                                'Completed' => 'success',
                                'Pending', 'Processing' => 'warning',
                                default => 'gray',
                            }),
                        TextEntry::make('attempt')->formatStateUsing(static fn (ProcessingState $record): string => "{$record->attempt} of {$record->max_attempts}"),
                        TextEntry::make('content_version')->label('Content version'),
                        TextEntry::make('sequence')->label('Event sequence'),
                        TextEntry::make('started_at')->dateTime()->placeholder('-'),
                        TextEntry::make('finished_at')->dateTime()->placeholder('-'),
                        TextEntry::make('updated_at')->label('Last progress')->dateTime(),
                        TextEntry::make('duration')
                            ->state(static fn (ProcessingState $record): ?string => ProcessingStatesTable::duration($record))
                            ->placeholder('-'),
                    ])
                    ->columns(4),
                Section::make('Error')
                    ->schema([
                        TextEntry::make('error_code')->placeholder('-'),
                        TextEntry::make('error_message')
                            ->placeholder('None')
                            ->columnSpanFull()
                            ->extraAttributes(['style' => 'white-space: pre-wrap; font-family: monospace']),
                    ])
                    ->visible(static fn (ProcessingState $record): bool => $record->error_message !== null || $record->error_code !== null),
                Section::make('Metadata')
                    ->schema([
                        TextEntry::make('metadata_json')
                            ->hiddenLabel()
                            ->state(static fn (ProcessingState $record): string => (string) json_encode($record->metadata ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                            ->extraAttributes(['style' => 'white-space: pre; font-family: monospace; overflow-x: auto']),
                    ])
                    ->collapsible(),
                Section::make('Other runs for this entity')
                    ->schema([
                        RepeatableEntry::make('related')
                            ->hiddenLabel()
                            ->state(static fn (ProcessingState $record): array => ProcessingState::query()
                                ->where('entity_type', $record->entity_type)
                                ->where('entity_id', $record->entity_id)
                                ->whereKeyNot($record->getKey())
                                ->orderByDesc('updated_at')
                                ->get()
                                ->map(static fn (ProcessingState $other): array => [
                                    'task' => ProcessingStatesTable::taskLabel($other->task_type),
                                    'status' => ucfirst($other->status->value),
                                    'updated_at' => $other->updated_at,
                                ])
                                ->all())
                            ->placeholder('No other tasks for this entity.')
                            ->schema([
                                TextEntry::make('task'),
                                TextEntry::make('status')->badge(),
                                TextEntry::make('updated_at')->label('Last progress')->dateTime(),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }
}
