<?php
namespace App\Domain\Articles\DTOs;

readonly class ArticleIncludeOptionsDTO
{
    public function __construct(
        public bool $include_user = true,
        public bool $include_kanjis = true,
        public bool $include_words = true,
        public bool $include_comments = false,
        public bool $include_views = false,
        public bool $include_downloads = false,
        public bool $include_likes = false,
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            include_user: $validated['include_user'] ?? true,
            include_kanjis: $validated['include_kanjis'] ?? true,
            include_words: $validated['include_words'] ?? true,
            include_comments: $validated['include_comments'] ?? false,
            include_views: $validated['include_views'] ?? false,
            include_downloads: $validated['include_downloads'] ?? false,
            include_likes: $validated['include_likes'] ?? false,
        );
    }

    public function hasEngagementFlags(): bool
    {
        return $this->include_views ||
            $this->include_likes ||
            $this->include_downloads ||
            $this->include_comments;
    }
}
