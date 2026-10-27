<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Words\ValueObjects;

/**
 * One numbered meaning of a dictionary word, read from its JMdict `sense` element.
 */
final readonly class WordSense
{
    /**
     * @param array<int, string> $partsOfSpeech
     * @param array<int, string> $glosses
     * @param array<int, string> $notes sense information (`s_inf`)
     * @param array<int, string> $misc usage, field and dialect tags (`misc`, `field`, `dial`)
     */
    public function __construct(
        public array $partsOfSpeech,
        public array $glosses,
        public array $notes,
        public array $misc,
    ) {
    }

    /**
     * Reads the import's raw `sense` column: a list of senses, each a list of
     * `[tag, value]` pairs whose value is an object keyed "0", sometimes with
     * `@attributes` beside it: `[[["pos",{"0":"noun"}],["gloss",{"0":"person"}]]]`.
     *
     * JMdict leaves `pos` out of a sense when it is the same as the previous one, so
     * a sense without its own parts of speech inherits them. Senses with no gloss are
     * dropped. Anything that is not that shape gives an empty list.
     *
     * @return array<int, self>
     */
    public static function listFromJmdict(?string $rawSense): array
    {
        $decoded = $rawSense === null ? null : json_decode($rawSense, true);

        if (! is_array($decoded)) {
            return [];
        }

        $senses = [];
        $previousPartsOfSpeech = [];

        foreach ($decoded as $senseEntry) {
            if (! is_array($senseEntry)) {
                continue;
            }

            $values = ['pos' => [], 'gloss' => [], 's_inf' => [], 'misc' => []];

            foreach ($senseEntry as $tagEntry) {
                if (! is_array($tagEntry) || ! is_string($tagEntry[0] ?? null)) {
                    continue;
                }

                $key = match ($tagEntry[0]) {
                    'pos' => 'pos',
                    'gloss' => 'gloss',
                    's_inf' => 's_inf',
                    'misc', 'field', 'dial' => 'misc',
                    default => null,
                };

                if ($key !== null) {
                    $values[$key] = [...$values[$key], ...self::textValues($tagEntry[1] ?? null)];
                }
            }

            if ($values['gloss'] === []) {
                continue;
            }

            $partsOfSpeech = $values['pos'] === [] ? $previousPartsOfSpeech : array_values(array_unique($values['pos']));
            $previousPartsOfSpeech = $partsOfSpeech;

            $senses[] = new self(
                partsOfSpeech: $partsOfSpeech,
                glosses: array_values(array_unique($values['gloss'])),
                notes: array_values(array_unique($values['s_inf'])),
                misc: array_values(array_unique($values['misc'])),
            );
        }

        return $senses;
    }

    /**
     * The text of one tag value. Keys starting with `@` hold XML attributes such as
     * a gloss type ("expl"), which are not part of the text.
     *
     * @return array<int, string>
     */
    private static function textValues(mixed $value): array
    {
        if (is_array($value)) {
            $texts = [];

            foreach ($value as $key => $nested) {
                if (is_string($key) && str_starts_with($key, '@')) {
                    continue;
                }

                $texts = [...$texts, ...self::textValues($nested)];
            }

            return $texts;
        }

        if (! is_scalar($value)) {
            return [];
        }

        $text = trim((string) $value);

        return $text === '' ? [] : [$text];
    }
}
