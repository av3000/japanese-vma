<?php

declare(strict_types=1);

namespace App\Mcp\Support;

use App\Domain\Shared\Enums\SavedListType;

/**
 * The kind of study list an MCP client can ask for: the lists that hold something a learner can
 * drill. The website's list type also has sentence, article, lyric and artist lists and a "known"
 * twin of each kind, none of which a tool offers.
 */
enum DeckKind: string
{
    case KANJI = 'kanji';
    case WORDS = 'words';
    case RADICALS = 'radicals';

    public function toListType(): SavedListType
    {
        return match ($this) {
            self::KANJI => SavedListType::KANJIS,
            self::WORDS => SavedListType::WORDS,
            self::RADICALS => SavedListType::RADICALS,
        };
    }

    public static function fromListType(SavedListType $type): ?self
    {
        return match ($type) {
            SavedListType::KANJIS => self::KANJI,
            SavedListType::WORDS => self::WORDS,
            SavedListType::RADICALS => self::RADICALS,
            default => null,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'value');
    }
}
