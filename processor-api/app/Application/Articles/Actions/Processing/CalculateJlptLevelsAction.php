<?php

declare(strict_types=1);

namespace App\Application\Articles\Actions\Processing;

use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\JlptLevel;
use App\Domain\Shared\ValueObjects\JlptLevels;

/**
 * Counts how many of an article's attached kanji sit at each JLPT level.
 *
 * Pure over domain kanji so the caller decides where they come from: the kanji job already
 * holds the resolved kanji from the character lookup, and the consolidated Phase 1 job can
 * feed it the same way. Kanji with no assigned level count as `uncommon`.
 */
final class CalculateJlptLevelsAction
{
    /**
     * @param iterable<Kanji> $kanjis
     */
    public function execute(iterable $kanjis): JlptLevels
    {
        $counts = [
            JlptLevel::N1 => 0,
            JlptLevel::N2 => 0,
            JlptLevel::N3 => 0,
            JlptLevel::N4 => 0,
            JlptLevel::N5 => 0,
        ];
        $uncommon = 0;

        foreach ($kanjis as $kanji) {
            $level = $kanji->getJlpt()?->value();

            if ($level !== null && array_key_exists($level, $counts)) {
                $counts[$level]++;
            } else {
                $uncommon++;
            }
        }

        return new JlptLevels(
            n1: $counts[JlptLevel::N1],
            n2: $counts[JlptLevel::N2],
            n3: $counts[JlptLevel::N3],
            n4: $counts[JlptLevel::N4],
            n5: $counts[JlptLevel::N5],
            uncommon: $uncommon,
        );
    }
}
