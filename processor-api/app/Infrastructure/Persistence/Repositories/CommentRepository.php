<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Domain\Comments\DTOs\CommentCreateDTO;
use App\Domain\Comments\DTOs\CommentCriteriaDTO;
use App\Domain\Comments\Models\Comment as DomainComment;
use App\Domain\Comments\Models\Comments;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Domain\Shared\ValueObjects\UserId;
use App\Infrastructure\Persistence\Models\Comment as PersistenceComment;
use App\Infrastructure\Persistence\Models\Like;
use Illuminate\Support\Str;
use RuntimeException;

class CommentRepository implements CommentRepositoryInterface
{
    public function findByCriteriaForEntity(CommentCriteriaDTO $criteria, ?int $viewerUserId): Comments
    {
        $query = PersistenceComment::with(['user'])
            ->where('template_id', $criteria->entityType->getLegacyId())
            ->where('real_object_id', $criteria->entityId)
            ->orderBy('created_at', 'DESC');

        $query->withCount('likes');

        if ($viewerUserId !== null) {
            $query->withExists(['likes as is_liked_by_viewer' => function ($q) use ($viewerUserId) {
                $q->where('user_id', $viewerUserId);
            }]);
        }

        $pagination = $criteria->pagination ?? Pagination::default();

        $paginatedResults = $query->paginate(
            $pagination->per_page,
            ['*'],
            'page',
            $pagination->page
        );

        // TODO: Implement include_replies
        // if ($parentOnly) {
        //     $query->whereNull('parent_comment_id');
        // }
        $domainComments = $paginatedResults->getCollection()->map(function ($persistenceComment) {
            return CommentMapper::mapToDomain($persistenceComment);
        });

        $paginatedResults->setCollection($domainComments);

        return Comments::fromEloquentPaginator($paginatedResults);
    }

    public function findByUuid(EntityId $commentUuid): ?DomainComment
    {
        $entity = PersistenceComment::with('user')
            ->withCount('likes')
            ->where('uuid', $commentUuid->value())
            ->first();

        return $entity ? CommentMapper::mapToDomain($entity) : null;
    }

    public function findById(int $commentId): ?DomainComment
    {
        $entity = PersistenceComment::with('user')
            ->withCount('likes')
            ->find($commentId);

        return $entity ? CommentMapper::mapToDomain($entity) : null;
    }

    public function createForEntity(CommentCreateDTO $dto, UserId $authorId): DomainComment
    {
        $persistenceComment = PersistenceComment::create([
            'uuid' => (string) Str::uuid(),
            'template_id' => $dto->entity_type->getLegacyId(),
            'real_object_id' => $dto->entity_id,
            'real_object_uuid' => $dto->entity_uuid->value(),
            'entity_type_uuid' => $dto->entity_type->value,
            'user_id' => $authorId->value(),
            'parent_comment_id' => $dto->parent_comment_id,
            'content' => $dto->content,
        ]);

        return CommentMapper::mapToDomain($this->reload($persistenceComment->id));
    }

    public function updateContent(int $commentId, string $content): DomainComment
    {
        $persistenceComment = PersistenceComment::query()->find($commentId);

        if ($persistenceComment === null) {
            throw new RuntimeException("Comment {$commentId} disappeared before it could be updated");
        }

        $persistenceComment->content = $content;
        $persistenceComment->save();

        return CommentMapper::mapToDomain($this->reload($commentId));
    }

    /**
     * Breadth-first walk of the reply tree, returning deepest levels first so
     * callers can delete children before their parents.
     *
     * @return int[]
     */
    public function collectDescendantIds(int $commentId): array
    {
        $levels = [];
        $currentLevel = [$commentId];

        while ($currentLevel !== []) {
            $childIds = PersistenceComment::query()
                ->whereIn('parent_comment_id', $currentLevel)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($childIds === []) {
                break;
            }

            $levels[] = $childIds;
            $currentLevel = $childIds;
        }

        return $levels === [] ? [] : array_merge(...array_reverse($levels));
    }

    /**
     * @param int[] $commentIds
     */
    public function deleteWithLikesByIds(array $commentIds): void
    {
        if ($commentIds === []) {
            return;
        }

        Like::query()
            ->where('template_id', ObjectTemplateType::COMMENT->getLegacyId())
            ->whereIn('real_object_id', $commentIds)
            ->delete();

        PersistenceComment::query()
            ->whereIn('id', $commentIds)
            ->delete();
    }

    /**
     * Replies are stored against the same entity as the comment they answer,
     * so selecting by entity already covers the whole thread at every depth.
     */
    public function deleteByEntity(int $entityId, int $entityTypeId): void
    {
        $commentIds = PersistenceComment::query()
            ->where('real_object_id', $entityId)
            ->where('template_id', $entityTypeId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->deleteWithLikesByIds($commentIds);
    }

    private function reload(int $commentId): PersistenceComment
    {
        return PersistenceComment::with('user')
            ->withCount('likes')
            ->findOrFail($commentId);
    }
}
