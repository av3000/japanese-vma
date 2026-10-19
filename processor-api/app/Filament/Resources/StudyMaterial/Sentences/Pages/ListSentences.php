<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudyMaterial\Sentences\Pages;

use App\Filament\Resources\StudyMaterial\Sentences\SentenceResource;
use Filament\Resources\Pages\ListRecords;

class ListSentences extends ListRecords
{
    protected static string $resource = SentenceResource::class;
}
