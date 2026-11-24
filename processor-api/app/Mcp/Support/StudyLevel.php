<?php

declare(strict_types=1);

namespace App\Mcp\Support;

/**
 * The JLPT level as an MCP client sees it: `N5` (easiest) to `N1`.
 *
 * The data stores the level in two other formats. Kanji and words share the dictionary format,
 * `"1"` to `"5"` with `"-"` for no level. Articles count kanji per level in columns named `n5`
 * to `n1` plus `uncommon`. Both read as `null` when the value is not one of the five levels, so
 * an unassigned or uncommon item has no level instead of a made-up one.
 */
enum StudyLevel: string
{
    case N5 = 'N5';
    case N4 = 'N4';
    case N3 = 'N3';
    case N2 = 'N2';
    case N1 = 'N1';

    public static function fromDictionary(?string $stored): ?self
    {
        if ($stored === null || strlen($stored) !== 1) {
            return null;
        }

        return self::tryFrom('N'.$stored);
    }

    public function toDictionary(): string
    {
        return substr($this->value, 1);
    }

    public static function fromArticle(string $column): ?self
    {
        return self::tryFrom(strtoupper($column));
    }

    public function toArticle(): string
    {
        return strtolower($this->value);
    }

    /**
     * @return array<int, string>
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'value');
    }
}
