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
            wordTypes: $this->parseListField($persistenceWord->word_type),
            writingElements: $this->parseListField($persistenceWord->word_k_ele),
            readingElements: $this->parseListField($persistenceWord->furigana_r_ele),
            meanings: $this->extractMeanings($persistenceWord->sense),
            rawWordType: $persistenceWord->word_type !== null ? (string) $persistenceWord->word_type : null,
            rawWritingElements: $persistenceWord->word_k_ele !== null ? (string) $persistenceWord->word_k_ele : null,
            rawReadingElements: $persistenceWord->furigana_r_ele !== null ? (string) $persistenceWord->furigana_r_ele : null,
            rawSense: $persistenceWord->sense !== null ? (string) $persistenceWord->sense : null,
        );
    }

    /**
     * If word source fields stabilize into a richer schema, replace this
     * pragmatic parser with dedicated value objects instead of widening this mapper.
     *
     * @return array<int, string>
     */
    private function parseListField(?string $value): array
    {
        if ($value === null || trim($value) === '' || trim($value) === '[]') {
            return [];
        }

        $decoded = json_decode($value, true);

        if (is_array($decoded)) {
            return array_values(array_filter(array_map(
                static fn (mixed $item): string => trim((string) $item),
                $decoded
            )));
        }

        return array_values(array_filter(array_map(
            'trim',
            preg_split('/[;|,]/', $value)
        )));
    }

    /**
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

                $value = $tagEntry[1] ?? null;
                $values = is_array($value) ? $value : [$value];

                foreach ($values as $singleValue) {
                    if (is_string($singleValue) && $singleValue !== '') {
                        $meanings[] = $singleValue;
                    }
                }
            }
        }

        return array_values(array_unique($meanings));
    }
}
