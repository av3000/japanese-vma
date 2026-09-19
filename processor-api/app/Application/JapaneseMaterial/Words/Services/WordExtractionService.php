<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Words\Services;

use App\Application\JapaneseMaterial\Words\Interfaces\Repositories\WordRepositoryInterface;

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
        $matchedWords = [];
        $matchedIds = [];
        $cursorStart = 0;

        while ($cursorStart < $characterCount) {
            $bestWord = null;
            $bestWordId = null;
            $candidate = '';
            $cursor = $cursorStart;

            while ($cursor < $characterCount) {
                $candidate .= $characters[$cursor];

                if (! $this->wordRepository->hasWordStartingWith($candidate)) {
                    break;
                }

                // Greedy longest match: remember the id of the longest candidate that is a real
                // word so the winner does not need a second lookup after the loop.
                $candidateId = $this->wordRepository->findIdByWord($candidate);

                if ($candidateId !== null) {
                    $bestWord = $candidate;
                    $bestWordId = $candidateId;
                }

                $cursor++;
            }

            if ($bestWord !== null && $bestWordId !== null) {
                if (! isset($matchedWords[$bestWord])) {
                    $matchedWords[$bestWord] = true;
                    $matchedIds[] = $bestWordId;
                }

                $cursorStart += mb_strlen($bestWord, 'UTF-8');

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
