<?php

declare(strict_types=1);

namespace App\Filament\Resources\TranslationLocales;

use App\Filament\AdminNavigationGroup;
use App\Filament\Resources\TranslationLocales\Pages\CreateTranslationLocale;
use App\Filament\Resources\TranslationLocales\Pages\EditTranslationLocale;
use App\Filament\Resources\TranslationLocales\Pages\ListTranslationLocales;
use App\Filament\Resources\TranslationLocales\Schemas\TranslationLocaleForm;
use App\Filament\Resources\TranslationLocales\Tables\TranslationLocalesTable;
use App\Infrastructure\Persistence\Models\TranslationLocale;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * The languages the interface can be shown in: add one, rename it, switch it on or off. Writes run
 * through TranslationLocaleAdminService; there is no delete.
 *
 * @extends Resource<TranslationLocale>
 */
class TranslationLocaleResource extends Resource
{
    protected static ?string $model = TranslationLocale::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Localisation;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Languages';

    protected static ?string $modelLabel = 'language';

    public static function form(Schema $schema): Schema
    {
        return TranslationLocaleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TranslationLocalesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTranslationLocales::route('/'),
            'create' => CreateTranslationLocale::route('/create'),
            'edit' => EditTranslationLocale::route('/{record}/edit'),
        ];
    }
}
