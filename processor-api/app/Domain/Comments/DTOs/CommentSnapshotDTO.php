<?php

declare(strict_types=1);

namespace App\Domain\Comments\DTOs;

/**
 * What the activity log keeps of a comment that was deleted for good: comments have no soft
 * delete, so this snapshot is the only record left of a removed reply.
 */
final readonly class CommentSnapshotDTO
{
    public function __construct(
        public string $uuid,
        public ?string $author,
        public string $content,
        public ?int $parentCommentId,
    ) {
    }

    /**
     * @return array{uuid: string, author: string|null, content: string, parent_comment_id: int|null}
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'author' => $this->author,
            'content' => $this->content,
            'parent_comment_id' => $this->parentCommentId,
        ];
    }
}
