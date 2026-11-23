<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Community\Posts\Enums\PostTopic;
use App\Domain\Community\Posts\Models\Post as DomainPost;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\Post as PersistencePost;
use DateTimeImmutable;
use RuntimeException;

class PostMapper
{
    public function mapToDomain(PersistencePost $post): DomainPost
    {
        $topic = PostTopic::tryFrom((int) $post->type);

        if ($topic === null) {
            throw new RuntimeException(
                "Post {$post->id} has topic code '{$post->type}' outside the canonical 1-7 range.",
            );
        }

        $author = $post->author;

        if ($author === null) {
            throw new RuntimeException("Post {$post->id} has no resolvable author.");
        }

        if ($post->created_at === null) {
            throw new RuntimeException("Post {$post->id} has no created_at timestamp.");
        }

        return new DomainPost(
            id: (int) $post->id,
            uuid: new EntityId((string) $post->uuid),
            title: (string) $post->title,
            content: (string) $post->content,
            topic: $topic,
            locked: (bool) $post->locked,
            authorId: (int) $author->id,
            authorUuid: new EntityId((string) $author->uuid),
            authorName: (string) $author->name,
            createdAt: DateTimeImmutable::createFromInterface($post->created_at),
            updatedAt: DateTimeImmutable::createFromInterface($post->updated_at ?? $post->created_at),
        );
    }
}
