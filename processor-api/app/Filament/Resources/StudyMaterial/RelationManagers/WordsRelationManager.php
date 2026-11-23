<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudyMaterial\RelationManagers;

use App\Filament\Resources\StudyMaterial\StudyMaterialOptions;
use App\Filament\Resources\StudyMaterial\Words\WordResource;
use App\Infrastructure\Persistence\Models\Word;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Words related to a kanji or sentence. A common kanji relates to thousands of words, so this is
 * paginated and never counted on list pages.
 */
class WordsRelationManager extends RelationManager
{
    protected static string $relationship = 'words';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('word')
            ->recordUrl(static fn (Word $record): string => WordResource::getUrl('view', ['record' => $record->uuid]))
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('word')->extraAttributes(['lang' => 'ja']),
                TextColumn::make('furigana')->extraAttributes(['lang' => 'ja']),
                TextColumn::make('jlpt')->label('JLPT')->formatStateUsing(static fn (?string $state): string => StudyMaterialOptions::jlptLabel($state)),
                TextColumn::make('sense')->label('Meaning')->formatStateUsing(static fn (?string $state): string => StudyMaterialOptions::meanings($state))->limit(60),
            ]);
    }
}
