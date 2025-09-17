<?php

namespace App\Domain\Shared\Enums;

enum ObjectTemplateType: int
{
    // TODO: there is some inconsistency with these from old ArticleController and database records
    // Although it was never issue because ObjectTemplate was always queried by title string
    // but it is confusing and should be solved.
    // | ID | title
    // |  1 | article
    // |  2 | artist
    // |  3 | lyric
    // |  4 | radical
    // |  5 | kanji
    // |  6 | word
    // |  7 | sentence
    // |  8 | list
    // |  9 | post
    // | 10 | comment
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
