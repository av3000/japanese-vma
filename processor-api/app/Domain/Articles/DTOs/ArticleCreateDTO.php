<?php
namespace App\Domain\Articles\DTOs;

readonly class ArticleCreateDTO
{
    public function __construct(
        public string $title_jp,
        public string $title_en,
        public string $content_jp,
        public string $content_en,
        public ?string $source_link,
        public int $publicity,
        public array $tags = []
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            title_jp: $data['title_jp'],
            title_en: $data['title_en'],
            content_jp: $data['content_jp'],
            content_en: $data['content_en'],
            source_link: $data['source_link'] ?? null,
            publicity: $data['publicity'],
            tags: $data['tags'] ?? []
        );
    }
}
