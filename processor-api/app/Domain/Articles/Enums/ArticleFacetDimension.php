<?php

declare(strict_types=1);

namespace App\Domain\Articles\Enums;

/**
 * The facet dimensions this program calculates.
 *
 * A disjunctive facet excludes its own dimension's active selection while keeping
 * every other filter, so selecting N5 does not collapse the JLPT list to just N5.
 * The dimension has to be nameable for that exclusion to be expressible.
 */
enum ArticleFacetDimension: string
{
    case JLPT_LEVELS = 'jlpt_levels';
    case HASHTAG_IDS = 'hashtag_ids';

    public function label(): string
    {
        return match ($this) {
            self::JLPT_LEVELS => 'JLPT level',
            self::HASHTAG_IDS => 'Hashtag',
        };
    }
}
