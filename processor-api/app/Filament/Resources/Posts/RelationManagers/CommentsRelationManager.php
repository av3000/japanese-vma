<?php

declare(strict_types=1);

namespace App\Filament\Resources\Posts\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only: deleting a comment belongs to the cross-entity Comments resource.
 */
class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('content')
            ->modifyQueryUsing(static fn ($query) => $query->with('user:id,name'))
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(10)
            ->columns([
                // Plain text: comment bodies are user input and never rendered as HTML.
                TextColumn::make('content')->searchable()->limit(100)->wrap(),
                TextColumn::make('user.name')->label('Author'),
                IconColumn::make('parent_comment_id')
                    ->label('Reply')
                    ->boolean()
                    ->state(static fn ($record): bool => $record->parent_comment_id !== null),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ]);
    }
}
