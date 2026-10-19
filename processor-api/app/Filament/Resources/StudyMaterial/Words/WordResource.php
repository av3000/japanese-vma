<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudyMaterial\Words;

use App\Filament\AdminNavigationGroup;
use App\Filament\Resources\StudyMaterial\RelationManagers\KanjisRelationManager;
use App\Filament\Resources\StudyMaterial\RelationManagers\SentencesRelationManager;
use App\Filament\Resources\StudyMaterial\StudyMaterialOptions;
use App\Filament\Resources\StudyMaterial\Words\Pages\ListWords;
use App\Filament\Resources\StudyMaterial\Words\Pages\ViewWord;
use App\Infrastructure\Persistence\Models\Word;
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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Read-only word browser over the ~185k-row dictionary. Pagination is simple (no total count) and
 * search is limited to the word and its reading.
 */
class WordResource extends Resource
{
    protected static ?string $model = Word::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::StudyMaterial;

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'word';

    protected static ?string $recordRouteKeyName = 'uuid';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->paginationMode(PaginationMode::Simple)
            ->columns([
                TextColumn::make('word')
                    ->extraAttributes(['lang' => 'ja'])
                    ->searchable(query: static fn (Builder $query, string $search): Builder => $query->where(static fn (Builder $inner) => $inner
                        ->where('word', trim($search))
                        ->orWhere('furigana', trim($search)))),
                TextColumn::make('furigana')->extraAttributes(['lang' => 'ja']),
                TextColumn::make('jlpt')->label('JLPT')->formatStateUsing(static fn (?string $state): string => StudyMaterialOptions::jlptLabel($state)),
                TextColumn::make('word_type')->label('Type')->limit(30)->toggleable(),
                TextColumn::make('sense')->label('Meaning')->formatStateUsing(static fn (?string $state): string => StudyMaterialOptions::meanings($state))->limit(60),
            ])
            ->filters([
                SelectFilter::make('jlpt')->label('JLPT')->options(StudyMaterialOptions::jlpt()),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Word')
                ->description(StudyMaterialOptions::ATTRIBUTION)
                ->schema([
                    TextEntry::make('word')->size('lg')->extraAttributes(['lang' => 'ja']),
                    TextEntry::make('furigana')->extraAttributes(['lang' => 'ja']),
                    TextEntry::make('jlpt')->label('JLPT')->formatStateUsing(static fn (?string $state): string => StudyMaterialOptions::jlptLabel($state)),
                    TextEntry::make('word_type')->label('Type')->placeholder('-'),
                    TextEntry::make('sense')->label('Meaning')->formatStateUsing(static fn (?string $state): string => StudyMaterialOptions::meanings($state))->columnSpanFull(),
                    TextEntry::make('entry_sequence')->label('JMdict entry')->placeholder('-'),
                    TextEntry::make('uuid')->label('UUID')->copyable(),
                ])
                ->columns(4),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            KanjisRelationManager::class,
            SentencesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWords::route('/'),
            'view' => ViewWord::route('/{record}'),
        ];
    }
}
