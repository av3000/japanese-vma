<?php
namespace App\Domain\Comments\Models;

use App\Domain\Shared\ValueObjects\{EntityId, UserId};

class Comment
{
    public function __construct(
        private $id,
        private EntityId $entityId,        // The entity this comment belongs to
        private string $entityType,       // 'article', 'list', etc.
        private UserId $authorId,
        private string $content,
        private ?EntityId $parentCommentId,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt
    ) {}

    // Factory for new comments
    public static function create(
        EntityId $entityId,
        string $entityType,
        UserId $authorId,
        string $content,
        ?EntityId $parentCommentId = null
    ): self {
        return new self(
            EntityId::generate(),
            $entityId,
            $entityType,
            $authorId,
            $content,
            $parentCommentId,
            new \DateTimeImmutable(),
            new \DateTimeImmutable()
        );
    }

    // Getters
    public function getId(): int { return $this->id; }
    public function getEntityId(): EntityId { return $this->entityId; }
    public function getEntityType(): string { return $this->entityType; }
    public function getAuthorId(): UserId { return $this->authorId; }
    public function getContent(): string { return $this->content; }
    public function getParentCommentId(): ?EntityId { return $this->parentCommentId; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    public function isReply(): bool
    {
        return $this->parentCommentId !== null;
    }
}
