<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Kanjis\Services;

class KanjiExtractionService implements KanjiExtractionServiceInterface
{
    /**
     * Extracts unique Kanji characters from a given Japanese text.
     *
     * @param string $text Japanese text content.
     * @return string[] An array of unique single Kanji characters found in the text.
     */
    public function extractUniqueKanjis(string $text): array
    {
        preg_match_all('/\p{Han}/u', $text, $matches);


        if (empty($matches[0])) {
            return [];
        }

        return array_values(array_unique($matches[0]));
    }
}
