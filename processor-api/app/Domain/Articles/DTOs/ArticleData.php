<?php

namespace App\Domain\Articles\DTOs;

class ArticleData
{
    public function __construct(
        public string $title_jp,
        public string $content_jp,
        public string $source_link,
        public ?string $title_en = null,
        public ?string $content_en = null,
        public bool $publicity = false,
        public ?int $user_id = null,
        public ?array $tags = []
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            title_jp: $validated['title_jp'],
            content_jp: $validated['content_jp'],
            source_link: $validated['source_link'],
            title_en: $validated['title_en'] ?? null,
            content_en: $validated['content_en'] ?? null,
            publicity: $validated['publicity'] ?? false,
            tags: isset($validated['tags']) ? explode(' ', $validated['tags']) : []
        );
    }
}
