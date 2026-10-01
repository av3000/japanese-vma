<?php

declare(strict_types=1);

namespace App\Domain\Catalogues\Enums;

use App\Domain\Shared\Enums\SavedListType;

/**
 * What a catalogue's JLPT counts are made of, by catalogue type (#386).
 *
 * Kanji and Words catalogues count their own items. Sentences catalogues count the distinct
 * words across all their sentences, and Articles catalogues the distinct kanji across all their
 * articles, so an entry shared by two items is counted once. Radicals carry no JLPT data, and
 * neither do lyrics or artists, so those types have no source.
 */
enum CatalogueJlptSource: string
{
    case KANJI = 'kanji';
    case WORDS = 'words';
    case SENTENCE_WORDS = 'sentence_words';
    case ARTICLE_KANJI = 'article_kanji';

    public static function forType(SavedListType $type): ?self
    {
        return match ($type) {
            SavedListType::KANJIS, SavedListType::KNOWNKANJIS => self::KANJI,
            SavedListType::WORDS, SavedListType::KNOWNWORDS => self::WORDS,
            SavedListType::SENTENCES, SavedListType::KNOWNSENTENCES => self::SENTENCE_WORDS,
            SavedListType::ARTICLES => self::ARTICLE_KANJI,
            default => null,
        };
    }
}
