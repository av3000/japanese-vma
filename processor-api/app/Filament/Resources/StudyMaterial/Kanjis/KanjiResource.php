<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudyMaterial\Kanjis;

use App\Filament\AdminNavigationGroup;
use App\Filament\Resources\StudyMaterial\Kanjis\Pages\ListKanjis;
use App\Filament\Resources\StudyMaterial\Kanjis\Pages\ViewKanji;
use App\Filament\Resources\StudyMaterial\RelationManagers\RadicalsRelationManager;
use App\Filament\Resources\StudyMaterial\RelationManagers\SentencesRelationManager;
use App\Filament\Resources\StudyMaterial\RelationManagers\WordsRelationManager;
use App\Filament\Resources\StudyMaterial\StudyMaterialOptions;
use App\Infrastructure\Persistence\Models\Kanji;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Read-only kanji browser. Imported dictionary rows stay read-only until manual corrections can
 * survive the importer.
 */
class KanjiResource extends Resource
{
    protected static ?string $model = Kanji::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::StudyMaterial;

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Kanji';

    protected static ?string $modelLabel = 'kanji';

    protected static ?string $pluralModelLabel = 'kanji';

    protected static ?string $recordTitleAttribute = 'kanji';

    protected static ?string $recordRouteKeyName = 'uuid';

    /**
     * Admins see the rows the public API hides for their '????' character (#545), so they can
     * find them when the source data can restore them.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScope(Kanji::READABLE_CHARACTER_SCOPE);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->columns([
                // Search uses the indexed character column only; meanings are not indexed.
                TextColumn::make('kanji')
                    ->size('lg')
                    ->extraAttributes(['lang' => 'ja'])
                    ->searchable(query: static fn (Builder $query, string $search): Builder => $query->where('kanji', trim($search))),
                TextColumn::make('meaning')->limit(50),
                TextColumn::make('onyomi')->limit(30)->extraAttributes(['lang' => 'ja']),
                TextColumn::make('kunyomi')->limit(30)->extraAttributes(['lang' => 'ja']),
                TextColumn::make('jlpt')->label('JLPT')->formatStateUsing(static fn (?string $state): string => StudyMaterialOptions::jlptLabel($state)),
                TextColumn::make('grade'),
                TextColumn::make('stroke_count')->label('Strokes')->sortable(),
                TextColumn::make('frequency')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('jlpt')->label('JLPT')->options(StudyMaterialOptions::jlpt()),
                SelectFilter::make('grade')->options(StudyMaterialOptions::grades()),
                Filter::make('strokes')
                    ->schema([
                        TextInput::make('min')->label('Strokes from')->numeric()->minValue(1),
                        TextInput::make('max')->label('Strokes to')->numeric()->minValue(1),
                    ])
                    ->query(static fn (Builder $query, array $data): Builder => $query
                        ->when(filled($data['min'] ?? null), static fn (Builder $q) => $q->where('stroke_count', '>=', (int) $data['min']))
                        ->when(filled($data['max'] ?? null), static fn (Builder $q) => $q->where('stroke_count', '<=', (int) $data['max']))),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Kanji')
                ->description(StudyMaterialOptions::ATTRIBUTION)
                ->schema([
                    TextEntry::make('kanji')->size('lg')->extraAttributes(['lang' => 'ja']),
                    TextEntry::make('meaning')->columnSpan(3),
                    TextEntry::make('onyomi')->extraAttributes(['lang' => 'ja'])->placeholder('-'),
                    TextEntry::make('kunyomi')->extraAttributes(['lang' => 'ja'])->placeholder('-'),
                    TextEntry::make('nanori')->extraAttributes(['lang' => 'ja'])->placeholder('-'),
                    TextEntry::make('jlpt')->label('JLPT')->formatStateUsing(static fn (?string $state): string => StudyMaterialOptions::jlptLabel($state)),
                    TextEntry::make('grade'),
                    TextEntry::make('stroke_count')->label('Strokes'),
                    TextEntry::make('frequency')->placeholder('-'),
                    TextEntry::make('radical_parts')->label('Radical parts')->extraAttributes(['lang' => 'ja'])->placeholder('-'),
                    TextEntry::make('uuid')->label('UUID')->copyable(),
                ])
                ->columns(4),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            WordsRelationManager::class,
            SentencesRelationManager::class,
            RadicalsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKanjis::route('/'),
            'view' => ViewKanji::route('/{record}'),
        ];
    }
}
