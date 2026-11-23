<?php

declare(strict_types=1);

namespace App\Filament\Resources\Comments\RelationManagers;

use App\Filament\Resources\Comments\CommentResource;
use App\Infrastructure\Persistence\Models\Comment;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RepliesRelationManager extends RelationManager
{
    protected static string $relationship = 'replies';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('content')
            ->modifyQueryUsing(static fn ($query) => $query->with('user:id,name')->withCount('replies'))
            ->recordUrl(static fn (Comment $record): string => CommentResource::getUrl('view', ['record' => $record->uuid]))
            ->defaultSort('created_at')
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('content')->limit(100)->wrap(),
                TextColumn::make('user.name')->label('Author'),
                TextColumn::make('replies_count')->label('Replies'),
                TextColumn::make('created_at')->dateTime(),
            ]);
    }
}
