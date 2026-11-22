<?php

declare(strict_types=1);

namespace App\Filament\Resources\TranslationLocales\Pages;

use App\Filament\Resources\TranslationLocales\Actions\TranslationLocaleActions;
use App\Filament\Resources\TranslationLocales\TranslationLocaleResource;
use Filament\Resources\Pages\ManageRecords;

/**
 * The resource's only page: the list, with create and edit as modals over it.
 */
class ManageTranslationLocales extends ManageRecords
{
    protected static string $resource = TranslationLocaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            TranslationLocaleActions::create(),
        ];
    }
}
