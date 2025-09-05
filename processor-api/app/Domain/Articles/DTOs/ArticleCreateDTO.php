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
            title_jp: $data['title_jp'],
            title_en: $data['title_en'] ?? null,
            content_jp: $data['content_jp'],
            content_en: $data['content_en'] ?? null,
            source_link: $data['source_link'],
            publicity: (bool)($data['publicity'] ?? false),
            tags: $data['tags'] ?? null
        );
    }
}
