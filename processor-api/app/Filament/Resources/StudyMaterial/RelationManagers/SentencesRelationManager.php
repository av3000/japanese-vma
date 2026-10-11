<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudyMaterial\RelationManagers;

use App\Filament\Resources\StudyMaterial\Sentences\SentenceResource;
use App\Infrastructure\Persistence\Models\Sentence;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Sentences that use a kanji or word. Read-only and paginated.
 */
class SentencesRelationManager extends RelationManager
{
    protected static string $relationship = 'sentences';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('content')
            ->recordUrl(static fn (Sentence $record): string => SentenceResource::getUrl('view', ['record' => $record->uuid]))
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('content')->wrap()->extraAttributes(['lang' => 'ja']),
                IconColumn::make('user_authored')
                    ->label('User-authored')
                    ->boolean()
                    ->state(static fn (Sentence $record): bool => $record->user_id !== null),
            ]);
    }
}
