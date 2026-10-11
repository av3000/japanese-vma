<?php

declare(strict_types=1);

namespace App\Filament\Resources\Comments\Tables;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Filament\Resources\Comments\Actions\CommentModerationActions;
use App\Filament\Resources\Comments\Support\CommentParents;
use App\Filament\Support\DateRangeFilter;
use App\Filament\Support\EntityReferenceLinks;
use App\Infrastructure\Persistence\Models\Comment;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CommentsTable
{
    /**
     * Entity types comments are written on today.
     */
    public const COMMENTABLE = [
        ObjectTemplateType::ARTICLE,
        ObjectTemplateType::LIST,
        ObjectTemplateType::POST,
        ObjectTemplateType::SENTENCE,
        ObjectTemplateType::KANJI,
        ObjectTemplateType::WORD,
        ObjectTemplateType::RADICAL,
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                // Plain text: comment bodies are user input and never rendered as HTML.
                TextColumn::make('content')
                    ->searchable()
                    ->limit(80)
                    ->wrap(),
                TextColumn::make('user.name')
                    ->label('Author'),
                TextColumn::make('template_id')
                    ->label('On')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(static fn (int $state): string => ObjectTemplateType::tryFromLegacyValue($state)?->label() ?? 'Unknown'),
                TextColumn::make('parent')
                    ->label('Parent')
                    ->state(static fn (Comment $record, $livewire): string => EntityReferenceLinks::label(CommentParents::of($record, $livewire->getTableRecords())))
                    ->url(static fn (Comment $record, $livewire): ?string => EntityReferenceLinks::url(CommentParents::of($record, $livewire->getTableRecords())))
                    ->color(static fn (Comment $record, $livewire): ?string => CommentParents::of($record, $livewire->getTableRecords())?->exists() ? 'primary' : 'gray')
                    ->limit(40),
                IconColumn::make('is_reply')
                    ->label('Reply')
                    ->boolean()
                    ->state(static fn (Comment $record): bool => $record->parent_comment_id !== null),
                TextColumn::make('replies_count')
                    ->label('Replies')
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
                SelectFilter::make('template_id')
                    ->label('Entity type')
                    ->options(self::entityTypeOptions()),
                SelectFilter::make('user_id')
                    ->label('Author')
                    ->relationship('user', 'name')
                    ->searchable(),
                Filter::make('has_replies')
                    ->label('Has replies')
                    ->toggle()
                    ->query(static fn (Builder $query): Builder => $query->whereHas('replies')),
                TernaryFilter::make('parent_comment_id')
                    ->label('Is a reply')
                    ->nullable(),
                DateRangeFilter::make('created_at', 'Written'),
            ])
            ->recordActions([
                ViewAction::make(),
                CommentModerationActions::delete(),
            ])
            ->toolbarActions([
                CommentModerationActions::bulkDelete(),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private static function entityTypeOptions(): array
    {
        $options = [];

        foreach (self::COMMENTABLE as $type) {
            $options[$type->getLegacyId()] = $type->label();
        }

        return $options;
    }
}
