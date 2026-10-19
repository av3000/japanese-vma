<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudyMaterial\Kanjis\Pages;

use App\Filament\Resources\StudyMaterial\Kanjis\KanjiResource;
use Filament\Resources\Pages\ListRecords;

class ListKanjis extends ListRecords
{
    protected static string $resource = KanjiResource::class;
}
