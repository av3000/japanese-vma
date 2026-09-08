<?php

namespace App\Application\Comments\Interfaces\Repositories;

use App\Domain\Comments\DTOs\CommentCreateDTO;
use App\Domain\Comments\DTOs\CommentCriteriaDTO;
use App\Domain\Comments\Models\Comment as DomainComment;
use App\Domain\Comments\Models\Comments;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;

interface CommentRepositoryInterface
{
    public function findByCriteriaForEntity(CommentCriteriaDTO $criteria, ?int $viewerUserId): Comments;

    public function findByUuid(EntityId $commentUuid): ?DomainComment;

    /**
     * Lookup by legacy numeric id, still needed because replies reference
     * their parent by `parent_comment_id`.
     */
    public function findById(int $commentId): ?DomainComment;

    public function createForEntity(CommentCreateDTO $dto, UserId $authorId): DomainComment;

    public function updateContent(int $commentId, string $content): DomainComment;

    /**
     * Every reply beneath the given comment, at any depth, deepest first.
     *
     * Nesting is not limited by the schema, so callers that clean up a thread
     * must walk the whole subtree rather than assuming a single reply level.
     *
     * @return int[]
     */
    public function collectDescendantIds(int $commentId): array;

    /**
     * Delete the given comments together with the Likes attached to them.
     *
     * Not transactional on its own - callers wrap this in the transaction that
     * owns the wider cleanup.
     *
     * @param int[] $commentIds
     */
    public function deleteWithLikesByIds(array $commentIds): void;

    public function deleteByEntity(int $entityId, int $entityTypeId): void;
}
