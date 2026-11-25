<?php

declare(strict_types=1);

namespace App\Mcp\Presenters;

use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji;
use App\Domain\JapaneseMaterial\Words\Models\Word;
use App\Mcp\Support\StudyLevel;

/**
 * Shapes a kanji for an assistant: what it needs to explain the character, and none of the
 * integer ids, nanori or frequency rank the website's own resources carry.
 */
final class KanjiPresenter
{
    /** A word can list a dozen glosses across its senses; three are enough to show what it means. */
    private const MEANING_GLOSSES = 3;

    /**
     * @return array{uuid: string, character: string, meanings: array<int, string>, onyomi: array<int, string>, kunyomi: array<int, string>, strokes: int, level: ?string, grade: ?int, radicals: array<int, string>}
     */
    public function present(Kanji $kanji): array
    {
        return [
            'uuid' => $kanji->getUuid()->value(),
            'character' => $kanji->getCharacter()->value(),
            'meanings' => $kanji->getMeanings(),
            'onyomi' => $kanji->getOnyomi(),
            'kunyomi' => $kanji->getKunyomi(),
            'strokes' => $kanji->getStrokeCount(),
            'level' => StudyLevel::fromDictionary($kanji->getJlpt()?->value())?->value,
            'grade' => $kanji->getGrade() === null ? null : (int) $kanji->getGrade()->value(),
            'radicals' => $kanji->getRadicals(),
        ];
    }

    /**
     * @param array<int, Word> $exampleWords
     *
     * @return array<string, mixed>
     */
    public function presentWithExamples(Kanji $kanji, array $exampleWords): array
    {
        return [
            ...$this->present($kanji),
            'example_words' => array_map($this->exampleWord(...), array_values($exampleWords)),
        ];
    }

    /**
     * @return array{word: string, reading: string, meaning: string}
     */
    private function exampleWord(Word $word): array
    {
        return [
            'word' => $word->getSurface(),
            'reading' => $word->getFurigana(),
            'meaning' => implode('; ', array_slice($word->getMeanings(), 0, self::MEANING_GLOSSES)),
        ];
    }
}
