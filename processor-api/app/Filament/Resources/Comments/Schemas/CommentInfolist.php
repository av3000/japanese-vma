<?php

declare(strict_types=1);

namespace App\Filament\Resources\Comments\Schemas;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Filament\Resources\Comments\CommentResource;
use App\Filament\Resources\Comments\Support\CommentParents;
use App\Infrastructure\Persistence\Models\Comment;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CommentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Comment')
                    ->schema([
                        // Plain text: comment bodies are user input and never rendered as HTML.
                        TextEntry::make('content')
                            ->label('')
                            ->columnSpanFull()
                            ->extraAttributes(['style' => 'white-space: pre-line']),
                        TextEntry::make('user.name')->label('Author'),
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('likes_count')->label('Likes'),
                        TextEntry::make('replies_count')->label('Direct replies'),
                    ])
                    ->columns(4),
                Section::make('Where it was written')
                    ->schema([
                        TextEntry::make('template_id')
                            ->label('Entity type')
                            ->formatStateUsing(static fn (int $state): string => ObjectTemplateType::tryFromLegacyValue($state)?->label() ?? 'Unknown'),
                        TextEntry::make('parent_entity')
                            ->label('Entity')
                            ->state(static fn (Comment $record): string => CommentParents::label(CommentParents::of($record)))
                            ->url(static fn (Comment $record): ?string => CommentParents::url(CommentParents::of($record))),
                        TextEntry::make('parentComment.content')
                            ->label('In reply to')
                            ->limit(200)
                            ->url(static fn (Comment $record): ?string => $record->parentComment === null
                                ? null
                                : CommentResource::getUrl('view', ['record' => $record->parentComment->uuid]))
                            ->placeholder('Not a reply')
                            ->columnSpanFull(),
                        TextEntry::make('uuid')->label('UUID')->copyable(),
                    ])
                    ->columns(3),
            ]);
    }
}
