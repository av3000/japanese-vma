<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\JapaneseMaterial\Words\Models\Word as DomainWord;
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
            writingElements: $this->extractStringListFromField($persistenceWord->word_k_ele),
            readingElements: $this->extractStringListFromField($persistenceWord->furigana_r_ele),
            meanings: $this->extractMeanings($persistenceWord->sense),
            rawWordType: $persistenceWord->word_type !== null ? (string) $persistenceWord->word_type : null,
            rawWritingElements: $persistenceWord->word_k_ele !== null ? (string) $persistenceWord->word_k_ele : null,
            rawReadingElements: $persistenceWord->furigana_r_ele !== null ? (string) $persistenceWord->furigana_r_ele : null,
            rawSense: $persistenceWord->sense !== null ? (string) $persistenceWord->sense : null,
        );
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
     * Reads the `sense` JSON and extracts nested meaning text.
     *
     * @return array<int, string>
     */
    private function extractMeanings(?string $sense): array
    {
        $decodedSense = json_decode($sense ?? '[]', true);

        if (! is_array($decodedSense)) {
            return [];
        }

        $meanings = [];

        foreach ($decodedSense as $senseEntry) {
            if (! is_array($senseEntry)) {
                continue;
            }

            foreach ($senseEntry as $tagEntry) {
                if (($tagEntry[0] ?? null) !== 'gloss') {
                    continue;
                }

                $meanings = array_merge(
                    $meanings,
                    $this->nestedStringValues($tagEntry[1] ?? null),
                );
            }
        }

        return array_values(array_unique($meanings));
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

        if (! is_scalar($value) || $value === null) {
            return [];
        }

        $stringValue = trim((string) $value);

        return $stringValue === '' ? [] : [$stringValue];
    }
}
