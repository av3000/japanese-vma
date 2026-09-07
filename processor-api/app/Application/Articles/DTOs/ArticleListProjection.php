<?php

declare(strict_types=1);

namespace App\Application\Articles\DTOs;

/**
 * Optional output cost for an Article list request.
 *
 * A projection may only make a response cheaper or richer. It must never change
 * which Articles are eligible - that is the scope's job - so nothing here is
 * allowed to reach the eligibility predicate.
 */
final readonly class ArticleListProjection
{
    public function __construct(
        public bool $includeStats = true,
        public bool $includeHashtags = true,
        public bool $includeKanjis = true,
        public bool $includeWords = true,
    ) {
    }

    /**
     * Internal callers that only need the rows, e.g. related-Article panels.
     */
    public static function itemsOnly(): self
    {
        return new self(false, false, false, false);
    }
}
