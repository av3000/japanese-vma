<?php

declare(strict_types=1);

namespace App\Domain\AdminDashboard\DTOs;

/**
 * One article waiting for a moderator, as the dashboard lists it.
 */
final readonly class ReviewQueueItemDTO
{
    public function __construct(
        public string $uuid,
        public string $title,
        public string $authorName,
        public int $status,
        public string $createdAt,
    ) {
    }

    /**
     * @return array{uuid: string, title: string, author_name: string, status: int, created_at: string}
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'author_name' => $this->authorName,
            'status' => $this->status,
            'created_at' => $this->createdAt,
        ];
    }

    /**
     * @param array{uuid: string, title: string, author_name: string, status: int, created_at: string} $values
     */
    public static function fromArray(array $values): self
    {
        return new self(
            uuid: $values['uuid'],
            title: $values['title'],
            authorName: $values['author_name'],
            status: $values['status'],
            createdAt: $values['created_at'],
        );
    }
}
