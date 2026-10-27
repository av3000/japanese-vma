<?php

declare(strict_types=1);

namespace App\Filament\Resources\Comments;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Filament\AdminNavigationGroup;
use App\Filament\Resources\Comments\Pages\ListComments;
use App\Filament\Resources\Comments\Pages\ViewComment;
use App\Filament\Resources\Comments\RelationManagers\RepliesRelationManager;
use App\Filament\Resources\Comments\Schemas\CommentInfolist;
use App\Filament\Resources\Comments\Tables\CommentsTable;
use App\Infrastructure\Persistence\Models\Comment;
use App\Infrastructure\Persistence\Models\Like;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Recent comments across every entity type, the only place the site's comments can be seen
 * together. Delete goes through CommentModerationServiceInterface and takes the comment's replies
 * with it.
 */
class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Community;

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'content';

    protected static ?string $recordRouteKeyName = 'uuid';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user:id,name', 'parentComment:id,uuid,content'])
            ->withCount('replies')
            ->addSelect([
                'likes_count' => Like::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('likes.real_object_id', 'comments.id')
                    ->where('likes.template_id', ObjectTemplateType::COMMENT->getLegacyId()),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CommentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RepliesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComments::route('/'),
            'view' => ViewComment::route('/{record}'),
        ];
    }
}
