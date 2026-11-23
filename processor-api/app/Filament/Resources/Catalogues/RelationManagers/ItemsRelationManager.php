<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogues\RelationManagers;

use App\Filament\Resources\Catalogues\Support\CatalogueItemLabels;
use App\Filament\Support\EntityReferenceLinks;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\CatalogueItem;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only: items belong to the catalogue's owner.
 */
class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        /** @var Catalogue $catalogue */
        $catalogue = $this->getOwnerRecord();
        $itemType = CatalogueItemLabels::itemType($catalogue->type);

        return $table
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->columns([
                TextColumn::make('item')
                    ->label($itemType?->label() ?? 'Item')
                    ->state(static fn (CatalogueItem $record, $livewire): string => EntityReferenceLinks::label(
                        CatalogueItemLabels::of($record, $itemType, $livewire->getTableRecords()),
                    ))
                    ->url(static fn (CatalogueItem $record, $livewire): ?string => EntityReferenceLinks::url(
                        CatalogueItemLabels::of($record, $itemType, $livewire->getTableRecords()),
                    ))
                    ->extraAttributes(['lang' => 'ja']),
                TextColumn::make('real_object_id')->label('Item id'),
                TextColumn::make('created_at')->label('Added')->dateTime(),
            ]);
    }
}
