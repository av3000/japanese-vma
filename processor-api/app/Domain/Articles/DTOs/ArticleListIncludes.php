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
     * Internal callers that only need the rows, e.g. related-Article panels.
     */
    public static function itemsOnly(): self
    {
        return new self(false, false, false, false, false);
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
