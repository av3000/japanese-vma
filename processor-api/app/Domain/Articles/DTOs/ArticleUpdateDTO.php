<?php
namespace App\Domain\Articles\DTOs;

readonly class ArticleUpdateDTO
{
    public function __construct(
        public ?string $title_jp = null,
        public ?string $title_en = null,
        public bool $title_en_present = false,
        public ?string $content_jp = null,
        public ?string $content_en = null,
        public bool $content_en_present = false,
        public ?string $source_link = null,
        public ?bool $publicity = null,
        public ?array $hashtags = null
    ) {}
    public static function fromRequest(array $validated): self
    {
        $titleEnPresent = array_key_exists('title_en', $validated);
        $contentEnPresent = array_key_exists('content_en', $validated);

        return new self(
            title_jp: $validated['title_jp'] ?? null,
            title_en: $titleEnPresent ? $validated['title_en'] : null,
            title_en_present: $titleEnPresent,
            content_jp: $validated['content_jp'] ?? null,
            content_en: $contentEnPresent ? $validated['content_en'] : null,
            content_en_present: $contentEnPresent,
            source_link: $validated['source_link'] ?? null,
            publicity: array_key_exists('publicity', $validated) ? (bool)$validated['publicity'] : null,
            hashtags: $validated['hashtags'] ?? null
        );
    }
}
