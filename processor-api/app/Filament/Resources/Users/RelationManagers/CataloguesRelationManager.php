<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\RelationManagers;

use App\Domain\Shared\Enums\SavedListType;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CataloguesRelationManager extends RelationManager
{
    protected static string $relationship = 'catalogues';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('title')->searchable()->limit(50),
                TextColumn::make('type')->badge()->color('gray')
                    ->formatStateUsing(static fn (SavedListType $state): string => $state->label()),
                IconColumn::make('publicity')->label('Public')->boolean(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ]);
    }
}
