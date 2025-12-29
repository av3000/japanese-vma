<?php

namespace App\Application\JapaneseMaterial\Kanjis\Services;

interface KanjiExtractionServiceInterface
{
    /**
     * Extracts unique Kanji characters from a given Japanese text.
     *
     * @param string $text Japanese text content.
     * @return string[] An array of unique single Kanji characters found in the text.
     */
    public function extractUniqueKanjis(string $text): array;
}
