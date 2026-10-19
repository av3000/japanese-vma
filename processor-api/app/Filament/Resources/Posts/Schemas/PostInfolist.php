<?php

declare(strict_types=1);

namespace App\Filament\Resources\Posts\Schemas;

use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Posts\Tables\PostsTable;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PostInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Post')
                    ->schema([
                        TextEntry::make('title')->columnSpanFull(),
                        TextEntry::make('author.name')->label('Author'),
                        TextEntry::make('type')
                            ->label('Topic')
                            ->badge()
                            ->color('gray')
                            ->formatStateUsing(static fn (?string $state): string => PostsTable::topicLabel($state)),
                        IconEntry::make('locked')
                            ->boolean()
                            ->trueIcon('heroicon-o-lock-closed')
                            ->falseIcon('heroicon-o-lock-open')
                            ->helperText(PostResource::LOCKED_MEANING),
                        TextEntry::make('comments_count')->label('Comments'),
                        TextEntry::make('likes_count')->label('Likes'),
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('uuid')->label('UUID')->copyable(),
                        TextEntry::make('id')->label('Legacy id'),
                    ])
                    ->columns(4),
                Section::make('Content')
                    ->schema([
                        // Plain text: post bodies are user input and never rendered as HTML.
                        TextEntry::make('content')
                            ->label('')
                            ->columnSpanFull()
                            ->extraAttributes(['style' => 'white-space: pre-line']),
                    ]),
            ]);
    }
}
