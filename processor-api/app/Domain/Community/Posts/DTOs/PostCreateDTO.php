<?php

declare(strict_types=1);

namespace App\Domain\Community\Posts\DTOs;

use App\Domain\Community\Posts\Enums\PostTopic;
use App\Domain\Shared\ValueObjects\Tags;

final readonly class PostCreateDTO
{
    /**
     * @param array<int, string> $tags Normalized `#tag` strings, deduplicated.
     */
    public function __construct(
        public string $title,
        public string $content,
        public PostTopic $topic,
        public array $tags,
    ) {
    }

    /**
     * @param array{title: string, content: string, topic: int|string, tags?: array<int, string>|null} $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            title: trim($validated['title']),
            content: trim($validated['content']),
            topic: PostTopic::from((int) $validated['topic']),
            tags: Tags::fromArray($validated['tags'] ?? [])->getTagsArray(),
        );
    }
}
