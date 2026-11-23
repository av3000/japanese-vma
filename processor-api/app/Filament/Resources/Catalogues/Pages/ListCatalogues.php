<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogues\Pages;

use App\Filament\Resources\Catalogues\CatalogueResource;
use Filament\Resources\Pages\ListRecords;

class ListCatalogues extends ListRecords
{
    protected static string $resource = CatalogueResource::class;
}
