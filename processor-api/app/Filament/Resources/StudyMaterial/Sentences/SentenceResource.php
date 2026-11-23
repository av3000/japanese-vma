<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudyMaterial\Sentences;

use App\Filament\AdminNavigationGroup;
use App\Filament\Resources\StudyMaterial\RelationManagers\KanjisRelationManager;
use App\Filament\Resources\StudyMaterial\RelationManagers\WordsRelationManager;
use App\Filament\Resources\StudyMaterial\Sentences\Pages\ListSentences;
use App\Filament\Resources\StudyMaterial\Sentences\Pages\ViewSentence;
use App\Filament\Resources\StudyMaterial\StudyMaterialOptions;
use App\Infrastructure\Persistence\Models\Sentence;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Sentences, imported and user-authored, which share one table. Imported rows are read-only;
 * user-authored ones are removed, never edited, through SentenceModerationServiceInterface.
 */
class SentenceResource extends Resource
{
    protected static ?string $model = Sentence::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenter;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::StudyMaterial;

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'content';

    protected static ?string $recordRouteKeyName = 'uuid';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(static fn ($query) => $query->with('user:id,name'))
            ->defaultSort('id', 'desc')
            ->paginationMode(PaginationMode::Simple)
            ->columns([
                TextColumn::make('content')->searchable()->wrap()->extraAttributes(['lang' => 'ja']),
                TextColumn::make('user.name')->label('Author')->placeholder('Imported (Tatoeba)'),
                TextColumn::make('tatoeba_entry')->label('Tatoeba entry')->placeholder('-')->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('user_id')
                    ->label('Source')
                    ->nullable()
                    ->trueLabel('User-authored')
                    ->falseLabel('Imported'),
                SelectFilter::make('author')
                    ->label('Author')
                    ->relationship('user', 'name')
                    ->searchable(),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Sentence')
                ->description(static fn (Sentence $record): ?string => $record->user_id === null ? StudyMaterialOptions::ATTRIBUTION : null)
                ->schema([
                    TextEntry::make('content')->size('lg')->columnSpanFull()->extraAttributes(['lang' => 'ja']),
                    TextEntry::make('user.name')->label('Author')->placeholder('Imported (Tatoeba)'),
                    TextEntry::make('tatoeba_entry')->label('Tatoeba entry')->placeholder('-'),
                    TextEntry::make('uuid')->label('UUID')->copyable(),
                    TextEntry::make('editable')
                        ->label('Editing')
                        ->state(static fn (Sentence $record): string => $record->user_id === null
                            ? 'Read-only: imported rows are replaced by the next import'
                            : 'User-authored: can be edited or deleted'),
                ])
                ->columns(3),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            KanjisRelationManager::class,
            WordsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSentences::route('/'),
            'view' => ViewSentence::route('/{record}'),
        ];
    }
}
