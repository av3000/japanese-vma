<?php

declare(strict_types=1);

namespace App\Domain\Articles\DTOs;

/**
 * Optional enrichment for an Article list request.
 *
 * Includes may only make a response cheaper or richer. They must never change
 * which Articles are eligible - that is the scope's job - so nothing here is
 * allowed to reach the eligibility predicate.
 *
 * Implements ArticleIncludeOptionsInterface so the persistence mapper accepts it
 * directly; the list path carries one includes shape, not two.
 */
final readonly class ArticleListIncludes implements ArticleIncludeOptionsInterface
{
    public function __construct(
        public bool $includeStats = true,
        public bool $includeHashtags = true,
        public bool $includeKanjis = true,
        public bool $includeWords = true,
        /**
         * Off by default so homepage, dashboard and related-Article callers do not
         * pay aggregation cost for controls they never render.
         */
        public bool $includeFacets = false,
    ) {
    }

    /**
     * The HTTP edge. Expects IndexArticleRequest::validated(), whose boolean
     * fields are already real booleans.
     *
     * @param array<string, mixed> $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            includeStats: (bool) ($validated['include_stats_counts'] ?? true),
            includeHashtags: (bool) ($validated['include_hashtags'] ?? true),
            includeKanjis: (bool) ($validated['include_kanjis'] ?? true),
            includeWords: (bool) ($validated['include_words'] ?? true),
            includeFacets: (bool) ($validated['include_facets'] ?? false),
        );
    }

    /**
     * Internal callers that only need the rows.
     */
    public static function itemsOnly(): self
    {
        return new self(false, false, false, false, false);
    }

    /**
     * Related-Article panels on kanji and word detail: engagement counts and tags,
     * but not the nested kanji or word lists, and never facets.
     */
    public static function relatedPanel(): self
    {
        return new self(
            includeStats: true,
            includeHashtags: true,
            includeKanjis: false,
            includeWords: false,
            includeFacets: false,
        );
    }

    public function includeKanjis(): bool
    {
        return $this->includeKanjis;
    }

    public function includeWords(): bool
    {
        return $this->includeWords;
    }
}
