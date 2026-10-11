<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\RelationManagers;

use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Filament\Resources\Articles\ArticleResource;
use App\Infrastructure\Persistence\Models\Article;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ArticlesRelationManager extends RelationManager
{
    protected static string $relationship = 'articles';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title_jp')
            // The bodies are large and unused here.
            ->modifyQueryUsing(static fn (Builder $query): Builder => $query->select([
                'id', 'uuid', 'user_id', 'title_jp', 'title_en', 'status', 'publicity', 'created_at',
            ]))
            ->recordUrl(static fn (Article $record): ?string => ArticleResource::canView($record)
                ? ArticleResource::getUrl('view', ['record' => $record])
                : null)
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('title_jp')->label('Title')->searchable()->limit(50)
                    ->description(static fn ($record): ?string => $record->title_en),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(static fn (ArticleStatus $state): string => $state->label()),
                TextColumn::make('publicity')->badge()->color('gray')
                    ->formatStateUsing(static fn (PublicityStatus $state): string => $state->label()),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ]);
    }
}
