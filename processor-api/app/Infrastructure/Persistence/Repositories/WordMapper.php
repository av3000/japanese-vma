<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\JapaneseMaterial\Words\Models\Word as DomainWord;
use App\Domain\JapaneseMaterial\Words\ValueObjects\JmdictSense;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\Word as PersistenceWord;

class WordMapper
{
    public function mapToDomain(PersistenceWord $persistenceWord): DomainWord
    {
        return new DomainWord(
            id: (int) $persistenceWord->id,
            uuid: new EntityId((string) $persistenceWord->uuid),
            surface: (string) $persistenceWord->word,
            furigana: (string) $persistenceWord->furigana,
            jlpt: $persistenceWord->jlpt !== null ? (string) $persistenceWord->jlpt : null,
            wordTypes: $this->extractStringListFromField($persistenceWord->word_type),
            writingElements: $this->extractElementValues($persistenceWord->word_k_ele, 'keb'),
            readingElements: $this->extractElementValues($persistenceWord->furigana_r_ele, 'reb'),
            meanings: JmdictSense::glosses($persistenceWord->sense),
            rawWordType: $persistenceWord->word_type !== null ? (string) $persistenceWord->word_type : null,
            rawWritingElements: $persistenceWord->word_k_ele !== null ? (string) $persistenceWord->word_k_ele : null,
            rawReadingElements: $persistenceWord->furigana_r_ele !== null ? (string) $persistenceWord->furigana_r_ele : null,
            rawSense: $persistenceWord->sense !== null ? (string) $persistenceWord->sense : null,
        );
    }

    /**
     * Reads JMdict kanji (`k_ele`) or reading (`r_ele`) elements. The import stores them
     * as a list of elements, each a list of `[tag, value]` pairs:
     * `[[["reb",{"0":"ひと"}],["re_pri",{"0":"ichi1"}]],[["reb",{"0":"ヒト"}]]]`.
     * Only `$elementTag` (`keb` or `reb`) carries the form itself; the rest are priority
     * and restriction tags. Fields in any other shape fall back to a flat string list.
     *
     * @return array<int, string>
     */
    private function extractElementValues(?string $value, string $elementTag): array
    {
        $decoded = $value === null ? null : json_decode($value, true);

        if (! is_array($decoded) || ! $this->isTaggedElementList($decoded)) {
            return $this->extractStringListFromField($value);
        }

        $values = [];

        foreach ($decoded as $element) {
            foreach (is_array($element) ? $element : [] as $tagEntry) {
                if (is_array($tagEntry) && ($tagEntry[0] ?? null) === $elementTag) {
                    $values = array_merge($values, $this->nestedStringValues($tagEntry[1] ?? null));
                }
            }
        }

        return array_values(array_unique($values));
    }

    /**
     * @param array<int|string, mixed> $decoded
     */
    private function isTaggedElementList(array $decoded): bool
    {
        foreach ($decoded as $element) {
            if (is_array($element)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extracts a simple string list from a DB raw JSON encoded string field.
     *
     *
     * @return array<int, string>
     */
    private function extractStringListFromField(?string $value): array
    {
        if ($value === null || trim($value) === '' || trim($value) === '[]') {
            return [];
        }

        $decoded = json_decode($value, true);

        if (is_array($decoded)) {
            return $this->topLevelStringValues($decoded);
        }

        return $this->parseDelimitedStringList($value);
    }

    /**
     * @return array<int, string>
     */
    private function parseDelimitedStringList(string $value): array
    {
        return array_values(array_filter(array_map(
            'trim',
            preg_split('/[;|,]/', $value)
        )));
    }

    /**
     * @param array<int, mixed> $decoded
     *
     * @return array<int, string>
     */
    private function topLevelStringValues(array $decoded): array
    {
        return array_values(array_filter(array_map(
            static function (mixed $item): string {
                if (! is_scalar($item) && $item !== null) {
                    return '';
                }

                return trim((string) $item);
            },
            $decoded
        )));
    }

    /**
     * Every non-empty string at any depth of a JMdict value, such as `{"0":"ひと"}`.
     *
     * @return array<int, string>
     */
    private function nestedStringValues(mixed $value): array
    {
        if (is_array($value)) {
            $values = [];

            foreach ($value as $nestedValue) {
                $values = array_merge($values, $this->nestedStringValues($nestedValue));
            }

            return $values;
        }

        if (! is_scalar($value)) {
            return [];
        }

        $stringValue = trim((string) $value);

        return $stringValue === '' ? [] : [$stringValue];
    }
}
