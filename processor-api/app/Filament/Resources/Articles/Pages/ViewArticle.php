<?php

declare(strict_types=1);

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Resources\Articles\Actions\ArticleModerationActions;
use App\Filament\Resources\Articles\ArticleResource;
use Filament\Resources\Pages\ViewRecord;

class ViewArticle extends ViewRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ArticleModerationActions::setStatus(),
            ArticleModerationActions::togglePublicity(),
            ArticleModerationActions::reprocess(),
            ArticleModerationActions::delete(),
        ];
    }
}
