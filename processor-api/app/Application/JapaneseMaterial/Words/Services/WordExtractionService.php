<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Words\Services;

use App\Application\JapaneseMaterial\Words\Interfaces\Repositories\WordRepositoryInterface;

/**
 * Greedy longest-match tokenizer over the dictionary.
 *
 * The walk itself is unchanged: at every position take the longest candidate that is a real
 * dictionary word, emit its id once per distinct surface, and advance past it. What changed is
 * where the dictionary lives during the walk. Asking the database about each candidate as the
 * walk reached it cost roughly three queries per character; now every candidate substring of
 * the text is resolved up front in batched lookups, and the walk itself touches no database.
 */
class WordExtractionService implements WordExtractionServiceInterface
{
    public function __construct(
        private readonly WordRepositoryInterface $wordRepository,
    ) {
    }

    /**
     * @return array<int, int>
     */
    public function extractWordIds(string $text): array
    {
        $characters = $this->splitCharacters($this->normalizeText($text));
        $characterCount = count($characters);

        // The longest dictionary entry bounds how far a candidate can usefully grow. Reading it
        // from the dictionary rather than hardcoding a cap keeps the match set identical to the
        // per-character walk even for the handful of very long entries.
        $maxWordLength = $this->wordRepository->maxWordLength();

        if ($characterCount === 0 || $maxWordLength < 1) {
            return [];
        }

        $idsByWord = $this->wordRepository->findIdsByWords(
            $this->candidateSubstrings($characters, $maxWordLength),
        );

        return $this->walkLongestMatches($characters, $maxWordLength, $idsByWord);
    }

    /**
     * Every distinct substring of the text that could be a dictionary word, deduplicated so a
     * phrase repeated across the article is looked up once.
     *
     * @param array<int, string> $characters
     *
     * @return list<string>
     */
    private function candidateSubstrings(array $characters, int $maxWordLength): array
    {
        $characterCount = count($characters);
        $candidates = [];

        for ($start = 0; $start < $characterCount; $start++) {
            $candidate = '';

            for ($length = 0; $length < $maxWordLength && $start + $length < $characterCount; $length++) {
                $candidate .= $characters[$start + $length];
                $candidates[$candidate] = true;
            }
        }

        return array_keys($candidates);
    }

    /**
     * @param array<int, string> $characters
     * @param array<string, int> $idsByWord
     *
     * @return array<int, int>
     */
    private function walkLongestMatches(array $characters, int $maxWordLength, array $idsByWord): array
    {
        $characterCount = count($characters);
        $matchedWords = [];
        $matchedIds = [];
        $cursorStart = 0;

        while ($cursorStart < $characterCount) {
            $bestWord = null;
            $bestLength = 0;
            $candidate = '';

            for ($length = 0; $length < $maxWordLength && $cursorStart + $length < $characterCount; $length++) {
                $candidate .= $characters[$cursorStart + $length];

                if (isset($idsByWord[$candidate])) {
                    $bestWord = $candidate;
                    $bestLength = $length + 1;
                }
            }

            if ($bestWord !== null) {
                if (! isset($matchedWords[$bestWord])) {
                    $matchedWords[$bestWord] = true;
                    $matchedIds[] = $idsByWord[$bestWord];
                }

                $cursorStart += $bestLength;

                continue;
            }

            $cursorStart++;
        }

        return $matchedIds;
    }

    private function normalizeText(string $text): string
    {
        return str_replace(["\n", "\r", ' '], '', $text);
    }

    /**
     * @return array<int, string>
     */
    private function splitCharacters(string $text): array
    {
        return preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
