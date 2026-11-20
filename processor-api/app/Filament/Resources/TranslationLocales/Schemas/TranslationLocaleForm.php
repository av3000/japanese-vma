<?php

declare(strict_types=1);

namespace App\Filament\Resources\TranslationLocales\Schemas;

use App\Application\Translations\Services\TranslationLocaleAdminService;
use App\Domain\Translations\Models\TranslationLocale as DomainTranslationLocale;
use App\Infrastructure\Persistence\Models\TranslationLocale;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

/**
 * Create takes a code, a name and the active flag; edit takes the name and the flag. The code is
 * shown but never submitted on edit, and the service refuses a change anyway.
 */
class TranslationLocaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')
                ->label('Code')
                ->helperText('Two lowercase letters, for example da or sv.')
                ->required()
                ->disabledOn('edit')
                ->dehydrated(fn (string $operation): bool => $operation === 'create'),
            TextInput::make('name')
                ->label('Name')
                ->helperText('How the language names itself, for example Dansk.')
                ->required()
                ->maxLength(TranslationLocaleAdminService::MAX_NAME_LENGTH),
            Toggle::make('is_active')
                ->label('Active')
                ->helperText('Learners can choose an active language. English is always active.')
                ->default(false)
                ->disabled(fn (?TranslationLocale $record): bool => $record?->code === DomainTranslationLocale::FALLBACK_CODE),
        ]);
    }
}
