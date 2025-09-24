<?php
namespace App\Domain\Shared\Enums;

enum SavedListType: int
{
    // TODO: migrate to UUIDs
   case KNOWNRADICALS = 1;
    case KNOWNKANJIS = 2;
    case KNOWNWORDS = 3;
    case KNOWNSENTENCES = 4;
    case RADICALS = 5;
    case KANJIS = 6;
    case WORDS = 7;
    case SENTENCES = 8;
    case ARTICLES = 9;
    case LYRICS = 10;
    case ARTISTS = 11;

    public function label(): string
    {
        return match($this) {
            self::KNOWNRADICALS => 'Known Radicals',
            self::KNOWNKANJIS => 'Known Kanji',
            self::KNOWNWORDS => 'Known Words',
            self::KNOWNSENTENCES => 'Known Sentences',
            self::RADICALS => 'Radicals',
            self::KANJIS => 'Kanji',
            self::WORDS => 'Words',
            self::SENTENCES => 'Sentences',
            self::ARTICLES => 'Articles',
            self::LYRICS => 'Lyrics',
            self::ARTISTS => 'Artists',
        };
    }
}
