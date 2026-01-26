<?php

namespace App\Domain\Articles\DTOs;

readonly class ArticleIncludeOptionsDTO implements ArticleIncludeOptionsInterface
{
    public function __construct(
        public bool $include_user = true,
        public bool $include_kanjis = true,
        public bool $include_words = true,
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            include_user: $validated['include_user'] ?? true,
            include_kanjis: $validated['include_kanjis'] ?? true,
            include_words: $validated['include_words'] ?? true,
        );
    }

    public function includeKanjis(): bool
    {
        return $this->include_kanjis;
    }
}
