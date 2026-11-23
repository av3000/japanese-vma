<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogues\Pages;

use App\Filament\Resources\Catalogues\Actions\CatalogueModerationActions;
use App\Filament\Resources\Catalogues\CatalogueResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCatalogue extends ViewRecord
{
    protected static string $resource = CatalogueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CatalogueModerationActions::togglePublicity(),
            CatalogueModerationActions::delete(),
        ];
    }
}
