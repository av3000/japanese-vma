<?php
namespace App\Infrastructure\Persistence\Repositories;

use App\Infrastructure\Persistence\Models\Comment as PersistenceComment;
use App\Domain\Comments\Models\Comment as DomainComment;
use App\Domain\Shared\ValueObjects\{EntityId, UserId};
use App\Http\Models\ObjectTemplate;

class CommentMapper
{
    public static function mapToDomain(PersistenceComment $entity): DomainComment
    {
        $entityType = self::getEntityTypeFromTemplateId($entity->template_id);

        return new DomainComment(
            $entity->id,
            $entity->real_object_id,
            new EntityId($entity->uuid),
            $entityType,
            new UserId($entity->user_id),
            $entity->content,
            $entity->parent_comment_id ? new EntityId($entity->parent_comment_id) : null,
            $entity->created_at->toDateTimeImmutable(),
            $entity->updated_at->toDateTimeImmutable()
        );
    }

    public static function mapToEntity(DomainComment $comment): array
    {
        return [
            'id' => $comment->getIdValue(),
            'template_id' => self::getTemplateIdFromEntityType($comment->getEntityType()),
            'real_object_id' => $comment->getEntityId()->value(),
            'user_id' => $comment->getAuthorId()->value(),
            'parent_comment_id' => $comment->getParentCommentId()?->value(),
            'content' => $comment->getContent(),
            'created_at' => $comment->getCreatedAt(),
            'updated_at' => $comment->getUpdatedAt(),
        ];
    }

    private static function getEntityTypeFromTemplateId(int $templateId): string
    {
        static $templateCache = [];

        if (!isset($templateCache[$templateId])) {
            $template = ObjectTemplate::find($templateId);
            $templateCache[$templateId] = $template ? $template->title : 'unknown';
        }

        return $templateCache[$templateId];
    }

    private static function getTemplateIdFromEntityType(string $entityType): int
    {
        static $typeCache = [];

        if (!isset($typeCache[$entityType])) {
            $template = ObjectTemplate::where('title', $entityType)->first();
            $typeCache[$entityType] = $template ? $template->id : 1; // fallback
        }

        return $typeCache[$entityType];
    }
}
