<?php

declare(strict_types=1);

namespace App\Filament\Resources\Posts;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Filament\AdminNavigationGroup;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Posts\Pages\ViewPost;
use App\Filament\Resources\Posts\RelationManagers\CommentsRelationManager;
use App\Filament\Resources\Posts\Schemas\PostInfolist;
use App\Filament\Resources\Posts\Tables\PostsTable;
use App\Infrastructure\Persistence\Models\Like;
use App\Infrastructure\Persistence\Models\Post;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Community post moderation. Locking closes comments while the post stays visible; lock, unlock
 * and delete go through PostModerationServiceInterface.
 */
class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Community;

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Posts';

    protected static ?string $modelLabel = 'community post';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $recordRouteKeyName = 'uuid';

    public const LOCKED_MEANING = 'Locked: comments are closed, the post itself stays visible.';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('author:id,name')
            ->withCount('comments')
            ->addSelect([
                'likes_count' => Like::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('likes.real_object_id', 'posts.id')
                    ->where('likes.template_id', ObjectTemplateType::POST->getLegacyId()),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PostInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PostsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CommentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'view' => ViewPost::route('/{record}'),
        ];
    }
}
