<?php
namespace App\Domain\Articles\DTOs;

use InvalidArgumentException;

readonly class ArticleCreateDTO
{
    public function __construct(
        public string $title_jp,
        public ?string $title_en,
        public string $content_jp,
        public ?string $content_en,
        public string $source_link,
        public bool $publicity,
        public ?string $tags = null
    ) {}
    /**
     * Create an instance from request data.
     *
     * @param array $data
     * @return self
     */
    public static function fromRequest(array $data): self
    {
         return new self(
            title_jp: $validated['title_jp'],
            title_en: $validated['title_en'] ?? null,
            content_jp: $validated['content_jp'],
            content_en: $validated['content_en'] ?? null,
            source_link: $validated['source_link'],
            publicity: (bool)($validated['publicity'] ?? false),
            tags: $validated['tags'] ?? null
        );
    }
}
