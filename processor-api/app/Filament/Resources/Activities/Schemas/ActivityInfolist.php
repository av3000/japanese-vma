<?php

declare(strict_types=1);

namespace App\Filament\Resources\Activities\Schemas;

use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Activity\Enums\ActivitySource;
use App\Domain\Activity\Enums\ActivitySubjectType;
use App\Infrastructure\Persistence\Models\AdminActivity;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ActivityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Activity')
                    ->schema([
                        TextEntry::make('created_at')->label('When')->dateTime(),
                        TextEntry::make('actor_name')->label('Actor'),
                        TextEntry::make('action')
                            ->badge()
                            ->formatStateUsing(static fn (ActivityAction $state): string => $state->label()),
                        TextEntry::make('source')
                            ->formatStateUsing(static fn (ActivitySource $state): string => $state->label()),
                        TextEntry::make('subject_type')
                            ->label('Subject')
                            ->formatStateUsing(static fn (ActivitySubjectType $state): string => $state->label()),
                        TextEntry::make('subject_label')->label('Subject name')->placeholder('-'),
                        TextEntry::make('subject_id')->label('Subject identifier')->copyable(),
                    ])
                    ->columns(2),
                Section::make('Changes')
                    ->schema([
                        KeyValueEntry::make('changes_table')
                            ->label('')
                            ->keyLabel('Attribute')
                            ->valueLabel('Before → after')
                            ->state(static fn (AdminActivity $record): array => self::changeRows($record))
                            ->placeholder('No attribute changes recorded'),
                    ]),
                Section::make('Context')
                    ->schema([
                        KeyValueEntry::make('context_table')
                            ->label('')
                            ->state(static fn (AdminActivity $record): array => array_map(
                                self::stringify(...),
                                $record->context ?? [],
                            ))
                            ->placeholder('No extra context'),
                    ])
                    ->visible(static fn (AdminActivity $record): bool => ($record->context ?? []) !== []),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function changeRows(AdminActivity $record): array
    {
        $before = $record->changes['before'] ?? [];
        $after = $record->changes['after'] ?? [];
        $rows = [];

        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $attribute) {
            $from = array_key_exists($attribute, $before) ? self::stringify($before[$attribute]) : '—';
            $to = array_key_exists($attribute, $after) ? self::stringify($after[$attribute]) : '—';
            $rows[$attribute] = "{$from} → {$to}";
        }

        return $rows;
    }

    private static function stringify(mixed $value): string
    {
        return match (true) {
            $value === null => 'empty',
            is_bool($value) => $value ? 'yes' : 'no',
            is_array($value) => $value === [] ? 'none' : implode(', ', array_map(self::stringify(...), $value)),
            default => (string) $value,
        };
    }
}
