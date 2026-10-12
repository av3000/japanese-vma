<?php

declare(strict_types=1);

namespace App\Filament\Resources\Hashtags\Pages;

use App\Filament\Resources\Hashtags\HashtagResource;
use Filament\Resources\Pages\ListRecords;

class ListHashtags extends ListRecords
{
    protected static string $resource = HashtagResource::class;

    public function getSubheading(): string
    {
        return 'Tags that differ only in case or character width count as the same tag. Merge variants into one; renaming to an existing name is refused.';
    }
}
