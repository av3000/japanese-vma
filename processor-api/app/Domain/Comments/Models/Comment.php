<?php

namespace App\Domain\Comments\Models;

use App\Domain\Shared\ValueObjects\{EntityId, UserId};

class Comment
{
    public function __construct(
        private ?int $id,
        private EntityId $entityId,
        private string $entityType,
        private string $authorName,
        private UserId $authorId,
        private string $content,
        private ?EntityId $parentCommentId,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt
    ) {}

    public function getIdValue(): int
    {
        return $this->id;
    }
    public function getEntityId(): EntityId
    {
        return $this->entityId;
    }
    public function getEntityType(): string
    {
        return $this->entityType;
    }
    public function getAuthorId(): UserId
    {
        return $this->authorId;
    }
    public function getAuthorName(): string
    {
        return $this->authorName;
    }
    public function getContent(): string
    {
        return $this->content;
    }
    public function getParentCommentId(): ?EntityId
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

    public function isReply(): bool
    {
        return $this->parentCommentId !== null;
    }
}
