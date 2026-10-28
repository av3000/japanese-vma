<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Words\ValueObjects;

/**
 * Reads a word's JMdict `sense` JSON. A sense is a list of entries, each a list of
 * `[tag, value]` pairs; the English meanings are the `gloss` values, which may be nested.
 */
final class JmdictSense
{
    /**
     * The distinct English glosses, in the order they appear. Malformed or empty JSON gives none.
     *
     * @return list<string>
     */
    public static function glosses(?string $sense): array
    {
        $decodedSense = json_decode($sense ?? '[]', true);

        if (! is_array($decodedSense)) {
            return [];
        }

        $glosses = [];

        foreach ($decodedSense as $senseEntry) {
            if (! is_array($senseEntry)) {
                continue;
            }

            foreach ($senseEntry as $tagEntry) {
                if (! is_array($tagEntry) || ($tagEntry[0] ?? null) !== 'gloss') {
                    continue;
                }

                $glosses = array_merge($glosses, self::nestedStrings($tagEntry[1] ?? null));
            }
        }

        return array_values(array_unique($glosses));
    }

    /**
     * @return list<string>
     */
    private static function nestedStrings(mixed $value): array
    {
        if (is_array($value)) {
            $strings = [];

            foreach ($value as $nested) {
                $strings = array_merge($strings, self::nestedStrings($nested));
            }

            return $strings;
        }

        if (! is_scalar($value)) {
            return [];
        }

        $string = trim((string) $value);

        return $string === '' ? [] : [$string];
    }
}
