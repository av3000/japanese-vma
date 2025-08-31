<?php
namespace App\Domain\Articles\DTOs;

readonly class ArticleUpdateDTO
{
    public function __construct(
        public ?string $title_jp = null,
        public ?string $title_en = null,
        public ?string $content_jp = null,
        public ?string $content_en = null,
        public ?string $source_link = null,
        public ?bool $publicity = null,
        public ?int $status = null,
        public ?string $tags = null,
        public bool $reattach = false
    ) {}
    public static function fromRequest(array $validated): self
    {
        return new self(
            title_jp: $validated['title_jp'] ?? null,
            title_en: $validated['title_en'] ?? null,
            content_jp: $validated['content_jp'] ?? null,
            content_en: $validated['content_en'] ?? null,
            source_link: $validated['source_link'] ?? null,
            publicity: isset($validated['publicity']) ? (bool)$validated['publicity'] : null,
            status: $validated['status'] ?? null,
            tags: $validated['tags'] ?? null,
            reattach: $validated['reattach'] ?? false
        );
    }
}
