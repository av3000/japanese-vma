<?php

declare(strict_types=1);

namespace App\Domain\Study\Enums;

/**
 * What a flashcard shows (prompt) or asks for (answer). The same vocabulary serves
 * both sides; a valid card uses two different fields, see FlashcardConfig.
 */
enum FlashcardField: string
{
    /** The kanji glyph, the radical glyph, or the word's written surface. */
    case CHARACTER = 'character';

    /** English meaning(s). */
    case MEANING = 'meaning';

    /** Kanji on'yomi, stored in katakana. */
    case ONYOMI = 'onyomi';

    /** Kanji kun'yomi, stored in hiragana with a dot before the okurigana. */
    case KUNYOMI = 'kunyomi';

    /** Word furigana or radical reading. */
    case READING = 'reading';

    /**
     * Whether a learner can type this as an answer. A character answer needs an IME
     * round trip the typed mode cannot grade fairly, so it is always offered as options.
     */
    public function isTypeable(): bool
    {
        return $this !== self::CHARACTER;
    }
}
