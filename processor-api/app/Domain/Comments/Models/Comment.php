<?php

namespace App\Domain\Comments\Models;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;

class Comment
{
    public function __construct(
        private ?int $id,
        private EntityId $uuid,
        private int $entityId,
        private ?EntityId $entityUuid,
        private ObjectTemplateType $entityType,
        private ?string $authorName,
        private UserId $authorId,
        private string $content,
        private ?int $parentCommentId,
        private int $likesCount,
        private bool $isLikedByViewer,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt
    ) {
    }

    public function getIdValue(): int
    {
        return $this->id;
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
}
