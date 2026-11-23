<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudyMaterial\Words\Pages;

use App\Filament\Resources\StudyMaterial\Words\WordResource;
use Filament\Resources\Pages\ListRecords;

class ListWords extends ListRecords
{
    protected static string $resource = WordResource::class;

    public function getSubheading(): string
    {
        return 'Search matches a word or its reading exactly.';
    }
}
