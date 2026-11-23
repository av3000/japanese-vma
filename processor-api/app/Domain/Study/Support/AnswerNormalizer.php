<?php

declare(strict_types=1);

namespace App\Domain\Study\Support;

use App\Domain\Study\Enums\FlashcardField;
use Normalizer;

/**
 * Backend twin of the client's grading normalization, used for one decision only: is a
 * candidate distractor accidentally a correct answer? It may therefore be looser than the
 * client (it always folds kana script, the client only does so in lenient mode): a looser
 * match rejects more candidates, never fewer, and the client stays the single grader.
 */
final class AnswerNormalizer
{
    /** The dictionary's placeholder for "none"; never a real answer. */
    private const EMPTY_MARKER = '-';

    private const MACRONS = ['ā' => 'a', 'ī' => 'i', 'ū' => 'u', 'ē' => 'e', 'ō' => 'o'];

    public static function normalize(string $value, FlashcardField $field): string
    {
        $value = Normalizer::normalize($value, Normalizer::FORM_KC) ?: $value;
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

        return match ($field) {
            FlashcardField::MEANING => self::normalizeMeaning($value),
            FlashcardField::KUNYOMI => self::foldKana(str_replace('.', '', trim($value, '-'))),
            FlashcardField::ONYOMI => self::foldKana($value),
            FlashcardField::READING => self::foldKana(strtr(mb_strtolower($value), self::MACRONS)),
            FlashcardField::CHARACTER => $value,
        };
    }

    /**
     * @param array<int, string> $values
     *
     * @return list<string> Distinct normalized values, empty strings dropped.
     */
    public static function normalizeAll(array $values, FlashcardField $field): array
    {
        $normalized = [];

        foreach ($values as $value) {
            $key = self::normalize($value, $field);

            if ($key !== '' && $key !== self::EMPTY_MARKER) {
                $normalized[$key] = $key;
            }
        }

        return array_values($normalized);
    }

    private static function normalizeMeaning(string $value): string
    {
        $value = mb_strtolower($value);
        $value = preg_replace('/\([^)]*\)/u', '', $value) ?? $value;
        $value = preg_replace('/^to\s+/u', '', trim($value)) ?? $value;
        $value = rtrim($value, ' .,;:!?');

        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

    /** Full-width katakana to hiragana; everything else untouched. */
    private static function foldKana(string $value): string
    {
        return mb_convert_kana($value, 'c', 'UTF-8');
    }
}
