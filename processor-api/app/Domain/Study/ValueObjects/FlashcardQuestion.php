<?php

declare(strict_types=1);

namespace App\Domain\Study\ValueObjects;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Study\Enums\AnswerMode;
use App\Domain\Study\Enums\FlashcardField;
use App\Domain\Study\Enums\ScriptStrictness;
use InvalidArgumentException;

/**
 * What a card asks: the prompt field, the answer field, how the learner answers and how
 * strictly kana script is held. Valid against a catalogue type by the combination table
 * (epic #413):
 *
 * | Type     | Fields                              | Rule                                   |
 * |----------|-------------------------------------|----------------------------------------|
 * | Kanji    | character, meaning, onyomi, kunyomi | one side is `character`                |
 * | Words    | character, reading, meaning         | one side is `character`                |
 * | Radicals | character, meaning, reading         | one side is `character`                |
 *
 * Typed mode is only valid when the answer field is typeable (anything but `character`).
 * How a deck is cut from the catalogue (count, seed) is FlashcardConfig's concern; a
 * persisted session keeps the question and its card count, never a seed.
 */
final readonly class FlashcardQuestion
{
    public function __construct(
        public FlashcardField $prompt,
        public FlashcardField $answer,
        public AnswerMode $mode,
        public ScriptStrictness $script,
    ) {
        if ($this->prompt === $this->answer) {
            throw new InvalidArgumentException('Prompt and answer fields must differ');
        }

        if ($this->mode === AnswerMode::TYPED && ! $this->answer->isTypeable()) {
            throw new InvalidArgumentException('A character answer cannot be typed');
        }
    }

    /**
     * Catalogue types a deck can be built from. Known variants (1 to 3) are included: a
     * drill over items already marked learned is a review. Sentences carry no translation
     * and articles are not cards, so they are not here.
     */
    public static function supportsType(SavedListType $type): bool
    {
        return self::baseType($type) !== null;
    }

    /**
     * @return list<FlashcardField> Fields a card of this catalogue type may use on either side.
     */
    public static function fieldsFor(SavedListType $type): array
    {
        return match (self::baseType($type)) {
            SavedListType::KANJIS => [
                FlashcardField::CHARACTER,
                FlashcardField::MEANING,
                FlashcardField::ONYOMI,
                FlashcardField::KUNYOMI,
            ],
            SavedListType::WORDS,
            SavedListType::RADICALS => [
                FlashcardField::CHARACTER,
                FlashcardField::MEANING,
                FlashcardField::READING,
            ],
            default => [],
        };
    }

    public function isValidFor(SavedListType $type): bool
    {
        $fields = self::fieldsFor($type);

        if (! in_array($this->prompt, $fields, true) || ! in_array($this->answer, $fields, true)) {
            return false;
        }

        return $this->prompt === FlashcardField::CHARACTER || $this->answer === FlashcardField::CHARACTER;
    }

    /**
     * The custom type a known type reviews, or the type itself. Null for unsupported types.
     * Mirrors TemplateTypeClassifier::getBaseType for the three supported families; kept
     * here so the Domain layer does not depend on a service to answer a type question.
     */
    public static function baseType(SavedListType $type): ?SavedListType
    {
        return match ($type) {
            SavedListType::KANJIS, SavedListType::KNOWNKANJIS => SavedListType::KANJIS,
            SavedListType::WORDS, SavedListType::KNOWNWORDS => SavedListType::WORDS,
            SavedListType::RADICALS, SavedListType::KNOWNRADICALS => SavedListType::RADICALS,
            default => null,
        };
    }

    /** The engagement vocabulary's id for the items a catalogue type's cards are built from. */
    public static function itemTypeFor(SavedListType $type): ?ObjectTemplateType
    {
        return match (self::baseType($type)) {
            SavedListType::KANJIS => ObjectTemplateType::KANJI,
            SavedListType::WORDS => ObjectTemplateType::WORD,
            SavedListType::RADICALS => ObjectTemplateType::RADICAL,
            default => null,
        };
    }
}
