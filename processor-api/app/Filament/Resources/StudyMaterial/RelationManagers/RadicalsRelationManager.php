<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudyMaterial\RelationManagers;

use App\Filament\Resources\StudyMaterial\Radicals\RadicalResource;
use App\Infrastructure\Persistence\Models\Radical;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RadicalsRelationManager extends RelationManager
{
    protected static string $relationship = 'radicals';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('radical')
            ->recordUrl(static fn (Radical $record): string => RadicalResource::getUrl('view', ['record' => $record->uuid]))
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('radical')->size('lg')->extraAttributes(['lang' => 'ja']),
                TextColumn::make('meaning'),
                TextColumn::make('strokes'),
            ]);
    }
}
