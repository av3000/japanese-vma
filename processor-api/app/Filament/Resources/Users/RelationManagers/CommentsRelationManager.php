<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\RelationManagers;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Filament\Resources\Comments\CommentResource;
use App\Infrastructure\Persistence\Models\Comment;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
            ->recordUrl(static fn (Comment $record): ?string => CommentResource::canView($record)
                ? CommentResource::getUrl('view', ['record' => $record->uuid])
                : null)
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(10)
            ->columns([
                // Plain text: comment bodies are user input and never rendered as HTML.
                TextColumn::make('content')->searchable()->limit(80)->wrap(),
                TextColumn::make('template_id')->label('On')->badge()->color('gray')
                    ->formatStateUsing(static fn (int $state): string => ObjectTemplateType::tryFromLegacyValue($state)?->label() ?? 'Unknown'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ]);
    }
}
