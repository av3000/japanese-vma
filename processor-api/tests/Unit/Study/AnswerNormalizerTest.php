<?php

declare(strict_types=1);

namespace Tests\Unit\Study;

use App\Domain\Study\Enums\FlashcardField;
use App\Domain\Study\Support\AnswerNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AnswerNormalizerTest extends TestCase
{
    /**
     * @return iterable<string, array{FlashcardField, string, string}>
     */
    public static function vectors(): iterable
    {
        yield 'meaning: case, spacing, trailing punctuation' => [FlashcardField::MEANING, 'Rank  Next.', 'rank next'];
        yield 'meaning: leading "to"' => [FlashcardField::MEANING, 'to learn', 'learn'];
        yield 'meaning: parenthesised qualifier' => [FlashcardField::MEANING, 'learn (a skill)', 'learn'];
        yield 'meaning: full-width latin via NFKC' => [FlashcardField::MEANING, 'ｓｔｕｄｙ', 'study'];
        yield 'kunyomi: okurigana dot' => [FlashcardField::KUNYOMI, 'つ.ぐ', 'つぐ'];
        yield 'kunyomi: edge hyphen' => [FlashcardField::KUNYOMI, '-がた', 'がた'];
        yield 'kunyomi: katakana folded' => [FlashcardField::KUNYOMI, 'ツグ', 'つぐ'];
        yield 'onyomi: folded to hiragana so scripts compare equal' => [FlashcardField::ONYOMI, 'アク', 'あく'];
        yield 'reading: word katakana folded' => [FlashcardField::READING, 'ドウジョウ', 'どうじょう'];
        yield 'reading: romaji macron' => [FlashcardField::READING, 'bō', 'bo'];
        yield 'reading: romaji case' => [FlashcardField::READING, 'Ichi', 'ichi'];
        yield 'character: untouched apart from trim' => [FlashcardField::CHARACTER, ' 学 ', '学'];
    }

    #[DataProvider('vectors')]
    public function test_normalize(FlashcardField $field, string $input, string $expected): void
    {
        $this->assertSame($expected, AnswerNormalizer::normalize($input, $field));
    }

    public function test_normalize_all_dedupes_and_drops_blanks(): void
    {
        $this->assertSame(
            ['study', 'learn'],
            AnswerNormalizer::normalizeAll(['Study', 'study', '', 'to learn', '-'], FlashcardField::MEANING),
        );
    }
}
