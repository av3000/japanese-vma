<?php

namespace App\Domain\Articles\DTOs;

/**
 * What an article detail response carries beyond the article itself.
 *
 * `include_kanjis` and `include_words` default to false since #268: an article with hundreds
 * of attached words made every detail read, and every socket-driven refetch after processing,
 * pay for a list nothing on screen was showing. Callers that want them inline still ask; the
 * rest read `articles/{uuid}/kanjis` and `articles/{uuid}/words`, a page at a time.
 */
readonly class ArticleIncludeOptionsDTO implements ArticleIncludeOptionsInterface
{
    public function __construct(
        public bool $include_user = true,
        public bool $include_kanjis = false,
        public bool $include_words = false,
    ) {
    }

    public static function fromRequest(array $validated): self
    {
        return new self(
            include_user: $validated['include_user'] ?? true,
            include_kanjis: $validated['include_kanjis'] ?? false,
            include_words: $validated['include_words'] ?? false,
        );
    }

    public function includeKanjis(): bool
    {
        return $this->include_kanjis;
    }

    public function includeWords(): bool
    {
        return $this->include_words;
    }
}
