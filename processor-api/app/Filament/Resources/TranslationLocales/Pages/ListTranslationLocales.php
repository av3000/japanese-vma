<?php

declare(strict_types=1);

namespace App\Filament\Resources\TranslationLocales\Pages;

use App\Filament\Resources\TranslationLocales\TranslationLocaleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTranslationLocales extends ListRecords
{
    protected static string $resource = TranslationLocaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
