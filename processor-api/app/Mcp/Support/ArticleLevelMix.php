<?php

declare(strict_types=1);

namespace App\Mcp\Support;

use App\Domain\Shared\ValueObjects\JlptLevels;

/**
 * An article's kanji count per level in the shape every article tool returns it, easiest level first.
 * `uncommon` is the kanji with no JLPT level.
 */
final class ArticleLevelMix
{
    /**
     * @return array{N5: int, N4: int, N3: int, N2: int, N1: int, uncommon: int}
     */
    public static function fromCounts(JlptLevels $counts): array
    {
        return [
            'N5' => $counts->n5,
            'N4' => $counts->n4,
            'N3' => $counts->n3,
            'N2' => $counts->n2,
            'N1' => $counts->n1,
            'uncommon' => $counts->uncommon,
        ];
    }
}
