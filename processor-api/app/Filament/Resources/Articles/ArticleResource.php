<?php

declare(strict_types=1);

namespace App\Filament\Resources\Articles;

use App\Filament\AdminNavigationGroup;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Filament\Resources\Articles\Pages\ViewArticle;
use App\Filament\Resources\Articles\Schemas\ArticleInfolist;
use App\Filament\Resources\Articles\Tables\ArticlesTable;
use App\Infrastructure\Persistence\Models\Article;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Review-oriented article moderation. Status and publicity changes, reprocessing and delete all
 * go through application services; authoring stays in the React client.
 *
 * @extends Resource<Article>
 */
class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Content;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'title_jp';

    protected static ?string $recordRouteKeyName = 'uuid';

    /**
     * Everything the list shows. The article bodies are left out: they are large and only the
     * view page needs them.
     */
    public const LIST_COLUMNS = [
        'articles.id', 'articles.uuid', 'articles.user_id', 'articles.title_jp', 'articles.title_en',
        'articles.status', 'articles.publicity', 'articles.n1', 'articles.n2', 'articles.n3',
        'articles.n4', 'articles.n5', 'articles.uncommon', 'articles.created_at', 'articles.updated_at',
    ];

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('user:id,name')
            ->select('articles.*')
            ->withProcessingStatus()
            ->withHashtagCount();
    }

    public static function infolist(Schema $schema): Schema
    {
        return ArticleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ArticlesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticles::route('/'),
            'view' => ViewArticle::route('/{record}'),
        ];
    }
}
