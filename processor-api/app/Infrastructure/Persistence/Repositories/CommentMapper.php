<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Comments\Models\Comment as DomainComment;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Infrastructure\Persistence\Models\Comment as PersistenceComment;
use RuntimeException;

class CommentMapper
{
    public static function mapToDomain(PersistenceComment $entity): DomainComment
    {
        $entityType = ObjectTemplateType::tryFromLegacyValue($entity->template_id);

        if ($entityType === null) {
            throw new RuntimeException(
                "Comment {$entity->id} references unknown template id {$entity->template_id}"
            );
        }

        $authorUuid = $entity->user?->uuid;

        return new DomainComment(
            $entity->id,
            EntityId::from($entity->uuid),
            (int) $entity->real_object_id,
            $entity->real_object_uuid !== null ? EntityId::from($entity->real_object_uuid) : null,
            $entityType,
            // Left null for comments whose author row is gone; the response
            // contract keeps the field rather than dropping the comment.
            $entity->user?->name,
            $authorUuid !== null ? EntityId::from($authorUuid) : null,
            new UserId($entity->user_id),
            $entity->content,
            $entity->parent_comment_id,
            (int) ($entity->likes_count ?? 0),
            (bool) ($entity->is_liked_by_viewer ?? false),
            $entity->created_at->toDateTimeImmutable(),
            $entity->updated_at->toDateTimeImmutable(),
            // Replies are counted and attached by the repository after the
            // batched subtree read, not per row here.
            0,
            [],
        );
    }
}
