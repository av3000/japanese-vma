<?php

declare(strict_types=1);

namespace Tests\Unit\Study;

use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Study\Enums\AnswerMode;
use App\Domain\Study\Enums\FlashcardField;
use App\Domain\Study\Enums\ScriptStrictness;
use App\Domain\Study\ValueObjects\FlashcardConfig;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FlashcardConfigTest extends TestCase
{
    /**
     * @return iterable<string, array{SavedListType, FlashcardField, FlashcardField, AnswerMode, bool}>
     */
    public static function combinations(): iterable
    {
        $o = AnswerMode::OPTIONS;
        $t = AnswerMode::TYPED;
        $c = FlashcardField::CHARACTER;
        $m = FlashcardField::MEANING;
        $on = FlashcardField::ONYOMI;
        $kun = FlashcardField::KUNYOMI;
        $r = FlashcardField::READING;

        // Kanji: character on one side; meaning, onyomi, kunyomi on the other.
        yield 'kanji character→meaning typed' => [SavedListType::KANJIS, $c, $m, $t, true];
        yield 'kanji character→onyomi typed' => [SavedListType::KANJIS, $c, $on, $t, true];
        yield 'kanji character→kunyomi options' => [SavedListType::KANJIS, $c, $kun, $o, true];
        yield 'kanji meaning→character options' => [SavedListType::KANJIS, $m, $c, $o, true];
        yield 'kanji onyomi→character options' => [SavedListType::KANJIS, $on, $c, $o, true];
        yield 'known kanji character→meaning' => [SavedListType::KNOWNKANJIS, $c, $m, $o, true];
        yield 'kanji meaning→onyomi is not a card' => [SavedListType::KANJIS, $m, $on, $o, false];
        yield 'kanji character→reading is a word field' => [SavedListType::KANJIS, $c, $r, $o, false];

        // Words: character on one side; reading, meaning on the other.
        yield 'word character→reading typed' => [SavedListType::WORDS, $c, $r, $t, true];
        yield 'word character→meaning typed' => [SavedListType::WORDS, $c, $m, $t, true];
        yield 'word reading→character options' => [SavedListType::WORDS, $r, $c, $o, true];
        yield 'known words character→reading' => [SavedListType::KNOWNWORDS, $c, $r, $o, true];
        yield 'word character→onyomi is a kanji field' => [SavedListType::WORDS, $c, $on, $o, false];
        yield 'word meaning→reading is not a card' => [SavedListType::WORDS, $m, $r, $o, false];

        // Radicals: character on one side; meaning, reading on the other.
        yield 'radical character→meaning typed' => [SavedListType::RADICALS, $c, $m, $t, true];
        yield 'radical character→reading typed' => [SavedListType::RADICALS, $c, $r, $t, true];
        yield 'radical meaning→character options' => [SavedListType::RADICALS, $m, $c, $o, true];
        yield 'known radicals character→meaning' => [SavedListType::KNOWNRADICALS, $c, $m, $o, true];
        yield 'radical character→kunyomi is a kanji field' => [SavedListType::RADICALS, $c, $kun, $o, false];

        // Unsupported types have no fields at all.
        yield 'sentences' => [SavedListType::SENTENCES, $c, $m, $o, false];
        yield 'articles' => [SavedListType::ARTICLES, $c, $m, $o, false];
    }

    #[DataProvider('combinations')]
    public function test_combination_table(SavedListType $type, FlashcardField $prompt, FlashcardField $answer, AnswerMode $mode, bool $valid): void
    {
        $config = new FlashcardConfig($prompt, $answer, $mode, ScriptStrictness::STRICT, 20, 1);

        $this->assertSame($valid, $config->isValidFor($type));
    }

    public function test_supported_types_are_the_three_families_and_their_known_variants(): void
    {
        $supported = array_values(array_filter(SavedListType::cases(), FlashcardConfig::supportsType(...)));

        $this->assertEqualsCanonicalizing([
            SavedListType::KNOWNRADICALS,
            SavedListType::KNOWNKANJIS,
            SavedListType::KNOWNWORDS,
            SavedListType::RADICALS,
            SavedListType::KANJIS,
            SavedListType::WORDS,
        ], $supported);
    }

    public function test_prompt_and_answer_must_differ(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new FlashcardConfig(FlashcardField::CHARACTER, FlashcardField::CHARACTER, AnswerMode::OPTIONS, ScriptStrictness::STRICT, 20, 1);
    }

    public function test_a_character_answer_cannot_be_typed(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new FlashcardConfig(FlashcardField::MEANING, FlashcardField::CHARACTER, AnswerMode::TYPED, ScriptStrictness::STRICT, 20, 1);
    }

    public function test_count_is_bounded(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new FlashcardConfig(FlashcardField::CHARACTER, FlashcardField::MEANING, AnswerMode::OPTIONS, ScriptStrictness::STRICT, FlashcardConfig::MAX_COUNT + 1, 1);
    }

    public function test_only_character_is_not_typeable(): void
    {
        $this->assertFalse(FlashcardField::CHARACTER->isTypeable());

        foreach ([FlashcardField::MEANING, FlashcardField::ONYOMI, FlashcardField::KUNYOMI, FlashcardField::READING] as $field) {
            $this->assertTrue($field->isTypeable(), $field->value);
        }
    }
}
