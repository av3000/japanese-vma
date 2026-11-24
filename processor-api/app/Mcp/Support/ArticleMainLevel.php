<?php

declare(strict_types=1);

namespace App\Mcp\Support;

use App\Domain\Shared\ValueObjects\JlptLevels;

/**
 * How hard an article is, as one level an assistant can pick reading by.
 *
 * The article's level filter matches any article with at least one kanji of that level, so a
 * result says nothing about difficulty on its own. The main level is the easiest level whose kanji,
 * together with every easier level's kanji, make up at least 90% of the article's kanji. Kanji
 * outside N5 to N1 (`uncommon`) count in the total but never in a level, so an article full of
 * them has no main level.
 *
 * It is coverage rather than "the level with the most kanji" on purpose: the counts are unique
 * kanji per level, the easy levels make up a large share of any text, and the most-kanji rule
 * would label most hard articles N4 or N3 and ignore uncommon kanji.
 */
final class ArticleMainLevel
{
    /** Percent of the article's kanji a learner at the level must be able to read. */
    private const COVERAGE_PERCENT = 90;

    public static function fromCounts(JlptLevels $counts): ?StudyLevel
    {
        $total = $counts->n5 + $counts->n4 + $counts->n3 + $counts->n2 + $counts->n1 + $counts->uncommon;

        if ($total === 0) {
            return null;
        }

        $covered = 0;

        foreach (self::easiestFirst($counts) as $level => $kanji) {
            $covered += $kanji;

            // Whole numbers on both sides, so exactly 90% is never lost to float rounding.
            if ($covered * 100 >= self::COVERAGE_PERCENT * $total) {
                return StudyLevel::from($level);
            }
        }

        return null;
    }

    /**
     * @return array<string, int>
     */
    private static function easiestFirst(JlptLevels $counts): array
    {
        return [
            StudyLevel::N5->value => $counts->n5,
            StudyLevel::N4->value => $counts->n4,
            StudyLevel::N3->value => $counts->n3,
            StudyLevel::N2->value => $counts->n2,
            StudyLevel::N1->value => $counts->n1,
        ];
    }
}
