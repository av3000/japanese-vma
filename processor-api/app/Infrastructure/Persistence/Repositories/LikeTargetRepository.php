<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Engagement\Interfaces\Repositories\LikeTargetRepositoryInterface;
use App\Domain\Engagement\Enums\LikeTargetType;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\Comment;
use App\Infrastructure\Persistence\Models\Post;
use Illuminate\Database\Eloquent\Builder;

class LikeTargetRepository implements LikeTargetRepositoryInterface
{
    public function isVisibleTo(LikeTargetType $target, int $entityId, ?int $viewerUserId): bool
    {
        return match ($target) {
            LikeTargetType::ARTICLE => $this->articleIsVisible($entityId, $viewerUserId),
            LikeTargetType::CATALOGUE => $this->catalogueIsVisible($entityId, $viewerUserId),
            LikeTargetType::POST => Post::query()->whereKey($entityId)->exists(),
            LikeTargetType::COMMENT => $this->commentIsVisible($entityId, $viewerUserId),
        };
    }

    private function articleIsVisible(int $entityId, ?int $viewerUserId): bool
    {
        return Article::query()
            ->whereKey($entityId)
            ->where(fn (Builder $query) => $this->restrictToOwnedOrPublic(
                $query,
                PublicityStatus::PUBLIC->value,
                $viewerUserId
            ))
            ->exists();
    }

    private function catalogueIsVisible(int $entityId, ?int $viewerUserId): bool
    {
        return Catalogue::query()
            ->whereKey($entityId)
            ->where(fn (Builder $query) => $this->restrictToOwnedOrPublic($query, true, $viewerUserId))
            ->exists();
    }

    /**
     * A comment is as visible as the thing it hangs off.
     *
     * Only Article and Catalogue parents can hide a comment; Post and the Japanese
     * material templates have no visibility column, so an existing comment on one of
     * them is public. There is no comment-on-comment case - replies keep the root
     * entity in template_id and use parent_comment_id instead.
     */
    private function commentIsVisible(int $entityId, ?int $viewerUserId): bool
    {
        /** @var Comment|null $comment */
        $comment = Comment::query()
            ->whereKey($entityId)
            ->first(['template_id', 'real_object_id']);

        if ($comment === null) {
            return false;
        }

        return match (ObjectTemplateType::tryFromLegacyValue($comment->template_id)) {
            ObjectTemplateType::ARTICLE => $this->articleIsVisible($comment->real_object_id, $viewerUserId),
            ObjectTemplateType::LIST => $this->catalogueIsVisible($comment->real_object_id, $viewerUserId),
            default => true,
        };
    }

    /**
     * Public to everyone, private to its owner only.
     */
    private function restrictToOwnedOrPublic(Builder $query, mixed $publicValue, ?int $viewerUserId): void
    {
        $query->where('publicity', $publicValue);

        if ($viewerUserId !== null) {
            $query->orWhere('user_id', $viewerUserId);
        }
    }
}
