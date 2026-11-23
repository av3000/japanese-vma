<?php

declare(strict_types=1);

namespace App\Domain\Community\Posts\DTOs;

use App\Domain\Community\Posts\Enums\PostTopic;
use App\Domain\Shared\ValueObjects\Tags;

/**
 * Partial update. A null field was not supplied and must be left untouched,
 * mirroring the legacy `isset($request->title)` behaviour.
 *
 * `tags` carries absence the same way, but an empty array is a meaningful
 * instruction to clear every tag - so null and [] are not interchangeable here.
 */
final readonly class PostUpdateDTO
{
    /**
     * @param array<int, string>|null $tags
     */
    public function __construct(
        public ?string $title = null,
        public ?string $content = null,
        public ?PostTopic $topic = null,
        public ?array $tags = null,
    ) {
    }

    /**
     * @param array<string, mixed> $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            title: isset($validated['title']) ? trim((string) $validated['title']) : null,
            content: isset($validated['content']) ? trim((string) $validated['content']) : null,
            topic: isset($validated['topic']) ? PostTopic::from((int) $validated['topic']) : null,
            tags: array_key_exists('tags', $validated) && is_array($validated['tags'])
                ? Tags::fromArray($validated['tags'])->getTagsArray()
                : null,
        );
    }

    /**
     * @return array<string, string>
     */
    public function toAttributes(): array
    {
        $attributes = [];

        if ($this->title !== null) {
            $attributes['title'] = $this->title;
        }

        if ($this->content !== null) {
            $attributes['content'] = $this->content;
        }

        if ($this->topic !== null) {
            // `posts.type` stores topic codes as strings; see PostMapper.
            $attributes['type'] = (string) $this->topic->value;
        }

        return $attributes;
    }
}
