<?php
namespace App\Domain\Articles\DTOs;

readonly class ArticleIncludeOptionsDTO
{
    public function __construct(
        public bool $include_user = true,
        public bool $include_kanjis = true,
        public bool $include_words = true,
        public bool $include_comments = true,
        public bool $include_views = true,
        public bool $include_downloads = true,
        public bool $include_likes = true,
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            include_user: $validated['include_user'] ?? true,
            include_kanjis: $validated['include_kanjis'] ?? true,
            include_words: $validated['include_words'] ?? true,
            include_comments: $validated['include_comments'] ?? true,
            include_views: $validated['include_views'] ?? true,
            include_downloads: $validated['include_downloads'] ?? true,
            include_likes: $validated['include_likes'] ?? true,
        );
    }
}
