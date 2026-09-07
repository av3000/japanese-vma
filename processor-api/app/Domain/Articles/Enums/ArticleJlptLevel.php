<?php

declare(strict_types=1);

namespace App\Domain\Articles\Enums;

/**
 * The six JLPT difficulty buckets an Article carries a kanji count for.
 *
 * Each case names a real integer column on `articles`; an Article matches a level
 * when that column is greater than zero.
 */
enum ArticleJlptLevel: string
{
    case N5 = 'n5';
    case N4 = 'n4';
    case N3 = 'n3';
    case N2 = 'n2';
    case N1 = 'n1';
    case UNCOMMON = 'uncommon';

    /**
     * The column backing this level. Named explicitly so persistence code never
     * interpolates a raw request value into SQL.
     */
    public function column(): string
    {
        return $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::N5 => 'N5',
            self::N4 => 'N4',
            self::N3 => 'N3',
            self::N2 => 'N2',
            self::N1 => 'N1',
            self::UNCOMMON => 'Uncommon',
        };
    }

    /**
     * Deterministic easiest-to-hardest display order, used by AFM-05 facets.
     *
     * @return array<int, self>
     */
    public static function displayOrder(): array
    {
        return [self::N5, self::N4, self::N3, self::N2, self::N1, self::UNCOMMON];
    }

    /**
     * The legacy numeric `category` alias, 1..6. Retired by AFM-07.
     */
    public static function fromLegacyCategory(int $category): ?self
    {
        return match ($category) {
            1 => self::N1,
            2 => self::N2,
            3 => self::N3,
            4 => self::N4,
            5 => self::N5,
            6 => self::UNCOMMON,
            default => null,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
