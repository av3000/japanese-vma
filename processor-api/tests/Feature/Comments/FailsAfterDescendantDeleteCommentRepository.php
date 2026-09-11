<?php

declare(strict_types=1);

namespace Tests\Feature\Comments;

use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Domain\Comments\DTOs\CommentCreateDTO;
use App\Domain\Comments\DTOs\CommentCriteriaDTO;
use App\Domain\Comments\Models\Comment as DomainComment;
use App\Domain\Comments\Models\Comments;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Domain\Shared\ValueObjects\UserId;
use RuntimeException;

/**
 * Deletes the descendant replies for real, then fails before the comment
 * itself is removed. Used to prove the cascade runs inside one transaction:
 * the already-deleted replies must come back on rollback.
 */
class FailsAfterDescendantDeleteCommentRepository implements CommentRepositoryInterface
{
    public function __construct(private readonly CommentRepositoryInterface $inner)
    {
    }

    public function deleteWithLikesByIds(array $commentIds): void
    {
        // The service passes descendants first and the comment itself last.
        $descendantIds = array_slice($commentIds, 0, -1);

        $this->inner->deleteWithLikesByIds($descendantIds);

        throw new RuntimeException('Simulated failure after descendant cleanup');
    }

    public function findByCriteriaForEntity(CommentCriteriaDTO $criteria, ?int $viewerUserId): Comments
    {
        return $this->inner->findByCriteriaForEntity($criteria, $viewerUserId);
    }

    public function findRepliesForRoots(array $rootIds, int $limitPerRoot, ?int $viewerUserId): array
    {
        return $this->inner->findRepliesForRoots($rootIds, $limitPerRoot, $viewerUserId);
    }

    public function findRepliesByRoot(int $rootId, Pagination $pagination, ?int $viewerUserId): Comments
    {
        return $this->inner->findRepliesByRoot($rootId, $pagination, $viewerUserId);
    }

    public function findByUuid(EntityId $commentUuid): ?DomainComment
    {
        return $this->inner->findByUuid($commentUuid);
    }

    public function findById(int $commentId): ?DomainComment
    {
        return $this->inner->findById($commentId);
    }

    public function createForEntity(CommentCreateDTO $dto, UserId $authorId): DomainComment
    {
        return $this->inner->createForEntity($dto, $authorId);
    }

    public function updateContent(int $commentId, string $content): DomainComment
    {
        return $this->inner->updateContent($commentId, $content);
    }

    public function collectDescendantIds(int $commentId): array
    {
        return $this->inner->collectDescendantIds($commentId);
    }

    public function deleteByEntity(int $entityId, int $entityTypeId): void
    {
        $this->inner->deleteByEntity($entityId, $entityTypeId);
    }
}
