<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudyMaterial\Radicals;

use App\Filament\AdminNavigationGroup;
use App\Filament\Resources\StudyMaterial\Radicals\Pages\ListRadicals;
use App\Filament\Resources\StudyMaterial\Radicals\Pages\ViewRadical;
use App\Filament\Resources\StudyMaterial\RelationManagers\KanjisRelationManager;
use App\Filament\Resources\StudyMaterial\StudyMaterialOptions;
use App\Infrastructure\Persistence\Models\Radical;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Read-only radical browser. The table is small (a few hundred rows), so meanings are searchable.
 */
class RadicalResource extends Resource
{
    protected static ?string $model = Radical::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::StudyMaterial;

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'radical';

    protected static ?string $recordRouteKeyName = 'uuid';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('strokes')
            ->columns([
                TextColumn::make('radical')->size('lg')->extraAttributes(['lang' => 'ja'])->searchable(),
                TextColumn::make('meaning')->searchable(),
                TextColumn::make('hiragana')->extraAttributes(['lang' => 'ja'])->searchable(),
                TextColumn::make('strokes')->sortable(),
            ])
            ->filters([
                SelectFilter::make('strokes')
                    ->options(static fn (): array => Radical::query()->distinct()->orderBy('strokes')->pluck('strokes', 'strokes')->all()),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Radical')
                ->description(StudyMaterialOptions::ATTRIBUTION)
                ->schema([
                    TextEntry::make('radical')->size('lg')->extraAttributes(['lang' => 'ja']),
                    TextEntry::make('meaning'),
                    TextEntry::make('hiragana')->extraAttributes(['lang' => 'ja'])->placeholder('-'),
                    TextEntry::make('strokes'),
                    TextEntry::make('uuid')->label('UUID')->copyable(),
                ])
                ->columns(4),
        ]);
    }

    public static function getRelations(): array
    {
        return [KanjisRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRadicals::route('/'),
            'view' => ViewRadical::route('/{record}'),
        ];
    }
}
