<?php

namespace App\Domain\Comments\Models;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;

class Comment
{
    /**
     * @param  Comment[]  $replies  Loaded subtree, oldest first. Empty when the
     *                              caller did not ask for replies; a top-level
     *                              comment with `repliesCount > 0` and an empty
     *                              `replies` is a valid, expected state.
     */
    public function __construct(
        private ?int $id,
        private EntityId $uuid,
        private int $entityId,
        private ?EntityId $entityUuid,
        private ObjectTemplateType $entityType,
        private ?string $authorName,
        private ?EntityId $authorUuid,
        private UserId $authorId,
        private string $content,
        private ?int $parentCommentId,
        private int $likesCount,
        private bool $isLikedByViewer,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
        private int $repliesCount = 0,
        private array $replies = [],
    ) {
    }

    public function getIdValue(): int
    {
        return (int) $this->id;
    }

    public function getUuid(): EntityId
    {
        return $this->uuid;
    }

    /**
     * Legacy numeric identifier of the commented entity.
     *
     * TODO: drop once every commentable entity is addressed by UUID only.
     */
    public function getEntityId(): int
    {
        return $this->entityId;
    }

    /**
     * Null for legacy comments written before the entity UUID backfill, and
     * for rows the legacy parent-specific routes still create without one.
     */
    public function getEntityUuid(): ?EntityId
    {
        return $this->entityUuid;
    }

    public function getEntityUuidValue(): ?string
    {
        return $this->entityUuid?->value();
    }

    public function getEntityType(): ObjectTemplateType
    {
        return $this->entityType;
    }

    public function getAuthorId(): UserId
    {
        return $this->authorId;
    }

    public function getAuthorName(): ?string
    {
        return $this->authorName;
    }

    /**
     * Null when the author row is gone. The response drops the whole `author`
     * object in that case rather than emitting a name-shaped hole.
     */
    public function getAuthorUuid(): ?EntityId
    {
        return $this->authorUuid;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getParentCommentId(): ?int
    {
        return $this->parentCommentId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getLikesCount(): int
    {
        return $this->likesCount;
    }

    public function isLikedByViewer(): bool
    {
        return $this->isLikedByViewer;
    }

    public function isReply(): bool
    {
        return $this->parentCommentId !== null;
    }

    public function isAuthoredBy(UserId $userId): bool
    {
        return $this->authorId->equals($userId);
    }

    /**
     * Size of the whole subtree beneath this comment, at every depth.
     *
     * Independent of how many replies were actually loaded: a caller that asked
     * for three of forty still gets forty here, because that is the number a
     * "show all replies" control renders.
     */
    public function getRepliesCount(): int
    {
        return $this->repliesCount;
    }

    /**
     * @return Comment[]
     */
    public function getReplies(): array
    {
        return $this->replies;
    }

    /**
     * Replies are attached after the comment is mapped, because they are read
     * in one batched query for the whole page rather than per comment.
     *
     * @param  Comment[]  $replies
     */
    public function withReplies(array $replies, int $repliesCount): self
    {
        $clone = clone $this;
        $clone->replies = $replies;
        $clone->repliesCount = $repliesCount;

        return $clone;
    }
}
