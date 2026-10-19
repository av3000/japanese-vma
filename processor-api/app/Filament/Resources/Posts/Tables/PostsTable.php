<?php

declare(strict_types=1);

namespace App\Filament\Resources\Posts\Tables;

use App\Domain\Community\Posts\Enums\PostTopic;
use App\Filament\Resources\Posts\Actions\PostModerationActions;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Support\DateRangeFilter;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->searchable(['title', 'content'])
                    ->limit(70)
                    ->wrap(),
                TextColumn::make('author.name')
                    ->label('Author'),
                TextColumn::make('type')
                    ->label('Topic')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(static fn (?string $state): string => self::topicLabel($state)),
                IconColumn::make('locked')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->tooltip(PostResource::LOCKED_MEANING),
                TextColumn::make('comments_count')
                    ->label('Comments')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('likes_count')
                    ->label('Likes')
                    ->numeric(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('locked'),
                SelectFilter::make('type')
                    ->label('Topic')
                    ->options(self::topicOptions()),
                SelectFilter::make('user_id')
                    ->label('Author')
                    ->relationship('author', 'name')
                    ->searchable(),
                DateRangeFilter::make('created_at', 'Posted'),
            ])
            ->recordActions([
                ViewAction::make(),
                PostModerationActions::toggleLock(),
            ])
            ->toolbarActions([
                PostModerationActions::bulkLock(),
                PostModerationActions::bulkUnlock(),
            ]);
    }

    /**
     * `posts.type` stores PostTopic codes as strings; anything else predates the enum.
     */
    public static function topicLabel(?string $type): string
    {
        return PostTopic::tryFrom((int) $type)?->label() ?? 'Unknown ('.($type ?? 'none').')';
    }

    /**
     * @return array<string, string>
     */
    private static function topicOptions(): array
    {
        $options = [];

        foreach (PostTopic::cases() as $topic) {
            $options[(string) $topic->value] = $topic->label();
        }

        return $options;
    }
}
