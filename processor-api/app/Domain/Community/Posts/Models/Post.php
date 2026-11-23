<?php

declare(strict_types=1);

namespace App\Domain\Community\Posts\Models;

use App\Domain\Community\Posts\Enums\PostTopic;
use App\Domain\Shared\ValueObjects\EntityId;
use DateTimeImmutable;

/**
 * Persistence-free Post read model.
 *
 * No Eloquent model, query builder, Request, Resource, or paginator crosses the
 * Post repository port; this is the only Post shape the application layer sees.
 */
final readonly class Post
{
    public function __construct(
        private int $id,
        private EntityId $uuid,
        private string $title,
        private string $content,
        private PostTopic $topic,
        private bool $locked,
        private int $authorId,
        private EntityId $authorUuid,
        private string $authorName,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getTopic(): PostTopic
    {
        return $this->topic;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    public function getAuthorId(): int
    {
        return $this->authorId;
    }

    public function getAuthorUuid(): EntityId
    {
        return $this->authorUuid;
    }

    public function getAuthorName(): string
    {
        return $this->authorName;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
