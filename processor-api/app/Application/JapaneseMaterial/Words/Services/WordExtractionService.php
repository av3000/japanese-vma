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
            $candidate = '';
            $cursor = $cursorStart;

            while ($cursor < $characterCount) {
                $candidate .= $characters[$cursor];

                if (! $this->wordRepository->hasWordStartingWith($candidate)) {
                    break;
                }

                if ($this->wordRepository->findIdByWord($candidate) !== null) {
                    $bestWord = $candidate;
                }

                $cursor++;
            }

            if ($bestWord !== null) {
                $wordId = $this->wordRepository->findIdByWord($bestWord);

                if ($wordId !== null && ! isset($matchedWords[$bestWord])) {
                    $matchedWords[$bestWord] = true;
                    $matchedIds[] = $wordId;
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
