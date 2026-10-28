<?php

declare(strict_types=1);

namespace App\Domain\Search\DTOs;

/**
 * How many results each list would show for one keyword, for the Header search.
 *
 * Each count equals that list's `pagination.total` for the same keyword and viewer. A
 * null count means the list itself rejects the keyword (too short or too long for it),
 * so there is no total to show.
 */
final readonly class SearchCountsDTO
{
    public function __construct(
        public string $q,
        public ?int $articles,
        public ?int $kanji,
        public ?int $words,
        public ?int $sentences,
        public ?int $radicals,
    ) {
    }
}
