<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum CustomListType: int
{
    case KNOWN_RADICALS = 1;
    case KNOWN_KANJIS = 2;
    case KNOWN_WORDS = 3;
    case KNOWN_SENTENCES = 4;

    public function title(): string
    {
        return match ($this) {
            self::KNOWN_RADICALS => 'Known Radicals',
            self::KNOWN_KANJIS => 'Known Kanjis',
            self::KNOWN_WORDS => 'Known Words',
            self::KNOWN_SENTENCES => 'Known Sentences',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::KNOWN_RADICALS => 'Radicals which you marked as learned',
            self::KNOWN_KANJIS => 'Kanjis which you marked as learned',
            self::KNOWN_WORDS => 'Words which you marked as learned',
            self::KNOWN_SENTENCES => 'Sentences which you marked as learned',
        };
    }
}
