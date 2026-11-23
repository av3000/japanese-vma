<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudyMaterial\RelationManagers;

use App\Filament\Resources\StudyMaterial\Kanjis\KanjiResource;
use App\Filament\Resources\StudyMaterial\StudyMaterialOptions;
use App\Infrastructure\Persistence\Models\Kanji;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Kanji related to a radical, word or sentence. Read-only and paginated.
 */
class KanjisRelationManager extends RelationManager
{
    protected static string $relationship = 'kanjis';

    protected static ?string $title = 'Kanji';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('kanji')
            ->recordUrl(static fn (Kanji $record): string => KanjiResource::getUrl('view', ['record' => $record->uuid]))
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('kanji')->size('lg')->extraAttributes(['lang' => 'ja']),
                TextColumn::make('meaning')->limit(60),
                TextColumn::make('jlpt')->label('JLPT')->formatStateUsing(static fn (?string $state): string => StudyMaterialOptions::jlptLabel($state)),
                TextColumn::make('stroke_count')->label('Strokes'),
            ]);
    }
}
