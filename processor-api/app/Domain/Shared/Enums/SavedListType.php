<?php
namespace App\Domain\Shared\Enums;

enum SavedListType: int
{
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

    public function uuid(): string
    {
        return match($this) {
            self::KNOWNRADICALS => '0eea67ac-2676-4b68-947f-391eab2e1416',
            self::KNOWNKANJIS => '1c3f3b1e-2dcb-4f0c-8f7a-2e5e4f3b6c9a',
            self::KNOWNWORDS => '2a4b5c6d-3e7f-4a8b-9c0d-1e2f3a4b5c6d',
            self::KNOWNSENTENCES => '3b4c5d6e-4f7a-5b8c-9d0e-1f2a3b4c5d6e',
            self::RADICALS => '4c5d6e7f-5a8b-6c9d-0e1f-2a3b4c5d6e7f',
            self::KANJIS => '5d6e7f8a-6b9c-7d0e-1f2a-3b4c5d6e7f8a',
            self::WORDS => '6e7f8a9b-7c0d-8e1f-2a3b-4c5d6e7f8a9b',
            self::SENTENCES => '7f8a9b0c-8d1e-9f2a-3b4c-5d6e7f8a9b0c',
            self::ARTICLES => '8a9b0c1d-9e2f-0a3b-4c5d-6e7f8a9b0c1d',
            self::LYRICS => '9b0c1d2e-0f3a-1b4c-5d6e-7f8a9b0c1d2e',
            self::ARTISTS => '0c1d2e3f-1a4b-2c5d-6e7f-8a9b0c1d2e3f',
        };
    }
}
