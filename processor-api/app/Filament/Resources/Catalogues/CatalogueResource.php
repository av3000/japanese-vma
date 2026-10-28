<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogues;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Filament\AdminNavigationGroup;
use App\Filament\Resources\Catalogues\Pages\ListCatalogues;
use App\Filament\Resources\Catalogues\Pages\ViewCatalogue;
use App\Filament\Resources\Catalogues\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\Catalogues\Schemas\CatalogueInfolist;
use App\Filament\Resources\Catalogues\Tables\CataloguesTable;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\Comment;
use App\Infrastructure\Persistence\Models\Like;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Mostly read-only catalogue oversight. Publicity and delete go through
 * CatalogueModerationServiceInterface; titles, descriptions and items belong to their owners.
 */
class CatalogueResource extends Resource
{
    protected static ?string $model = Catalogue::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Content;

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $recordRouteKeyName = 'uuid';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('user:id,name,uuid')
            ->withCount('items')
            ->addSelect([
                'comments_count' => Comment::countSubquery(ObjectTemplateType::LIST, 'customlists.id'),
                'likes_count' => Like::countSubquery(ObjectTemplateType::LIST, 'customlists.id'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CatalogueInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CataloguesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCatalogues::route('/'),
            'view' => ViewCatalogue::route('/{record}'),
        ];
    }
}
