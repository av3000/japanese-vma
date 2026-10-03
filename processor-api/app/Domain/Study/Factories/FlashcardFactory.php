<?php

declare(strict_types=1);

namespace App\Domain\Study\Factories;

use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji;
use App\Domain\JapaneseMaterial\Radicals\Models\Radical;
use App\Domain\JapaneseMaterial\Words\Models\Word;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Study\Enums\FlashcardField;
use App\Domain\Study\Models\Flashcard;
use App\Domain\Study\ValueObjects\FlashcardQuestion;

/**
 * Turns a dictionary item into a card for a given question, or null when the item has
 * nothing to answer with. Pure: the only knowledge here is how each item type stores its
 * fields (`-` means empty, radical readings are `かな / romaji`, kana-only words have no
 * separate reading).
 */
final class FlashcardFactory
{
    private const EMPTY_MARKER = '-';

    private const RADICAL_READING_SEPARATOR = ' / ';

    public function fromKanji(Kanji $kanji, FlashcardQuestion $question): ?Flashcard
    {
        $fields = [
            FlashcardField::CHARACTER->value => [$kanji->getCharacter()->value()],
            FlashcardField::MEANING->value => self::clean($kanji->getMeanings()),
            FlashcardField::ONYOMI->value => self::clean($kanji->getOnyomi()),
            FlashcardField::KUNYOMI->value => self::clean($kanji->getKunyomi()),
        ];

        return $this->build(
            $kanji->getIdValue(),
            $kanji->getUuid()->value(),
            $fields,
            $question,
            hint: null,
            jlpt: $kanji->getJlpt()?->value(),
            grade: $kanji->getGrade()?->value(),
            strokes: $kanji->getStrokeCount(),
        );
    }

    public function fromWord(Word $word, FlashcardQuestion $question): ?Flashcard
    {
        $surface = $word->getSurface();
        $furigana = trim($word->getFurigana());

        // A kana-only entry reads as it is written; asking for its reading is asking for
        // the prompt back, so the reading side is treated as empty.
        $reading = ($furigana === '' || $furigana === self::EMPTY_MARKER || $furigana === $surface) ? [] : [$furigana];

        $fields = [
            FlashcardField::CHARACTER->value => [$surface],
            FlashcardField::MEANING->value => self::clean($word->getMeanings()),
            FlashcardField::READING->value => $reading,
        ];

        $jlpt = $word->getJlpt();

        return $this->build(
            $word->getIdValue(),
            $word->getUuid()->value(),
            $fields,
            $question,
            hint: $word->getWordTypes()[0] ?? null,
            jlpt: ($jlpt === null || $jlpt === self::EMPTY_MARKER) ? null : $jlpt,
            grade: null,
            strokes: null,
        );
    }

    public function fromRadical(Radical $radical, FlashcardQuestion $question): ?Flashcard
    {
        $glyph = $radical->getRadical();

        if ($glyph === null || trim($glyph) === '' || $glyph === self::EMPTY_MARKER) {
            return null;
        }

        $fields = [
            FlashcardField::CHARACTER->value => [$glyph],
            FlashcardField::MEANING->value => self::clean([$radical->getMeaning()]),
            FlashcardField::READING->value => self::splitRadicalReading($radical->getHiragana()),
        ];

        return $this->build(
            $radical->getIdValue(),
            $radical->getUuid()->value(),
            $fields,
            $question,
            hint: null,
            jlpt: null,
            grade: null,
            strokes: $radical->getStrokes(),
        );
    }

    /**
     * @param array<string, list<string>> $fields Field value => its non-empty values for this item.
     */
    private function build(
        int $itemId,
        string $itemUuid,
        array $fields,
        FlashcardQuestion $question,
        ?string $hint,
        ?string $jlpt,
        ?string $grade,
        ?int $strokes,
    ): ?Flashcard {
        $promptValues = $fields[$question->prompt->value] ?? [];
        $answerValues = $fields[$question->answer->value] ?? [];

        if ($promptValues === [] || $answerValues === []) {
            return null;
        }

        return new Flashcard(
            itemId: $itemId,
            itemUuid: new EntityId($itemUuid),
            promptText: implode(', ', $promptValues),
            promptHint: $hint,
            acceptedAnswers: $answerValues,
            displayAnswer: $answerValues[0],
            options: null,
            jlpt: $jlpt,
            grade: $grade,
            strokes: $strokes,
        );
    }

    /**
     * Drops the `-` placeholder the dictionary uses for "none", blanks, and duplicates.
     *
     * @param array<int, string|null> $values
     *
     * @return list<string>
     */
    private static function clean(array $values): array
    {
        $cleaned = [];

        foreach ($values as $value) {
            $value = trim((string) $value);

            if ($value === '' || $value === self::EMPTY_MARKER) {
                continue;
            }

            $cleaned[$value] = $value;
        }

        return array_values($cleaned);
    }

    /**
     * `いち / ichi` → `['いち', 'ichi']`. Either half is an accepted answer.
     *
     * @return list<string>
     */
    private static function splitRadicalReading(?string $reading): array
    {
        if ($reading === null) {
            return [];
        }

        return self::clean(explode(self::RADICAL_READING_SEPARATOR, $reading));
    }
}
