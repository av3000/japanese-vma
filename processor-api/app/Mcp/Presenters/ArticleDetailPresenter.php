<?php

declare(strict_types=1);

namespace App\Mcp\Presenters;

use App\Domain\Articles\Models\Article;
use App\Domain\JapaneseMaterial\Words\Models\Word;
use App\Mcp\Support\ArticleLevelMix;
use App\Mcp\Support\ArticleMainLevel;
use App\Mcp\Support\BoundedText;
use App\Mcp\Support\StudyLevel;

/**
 * Shapes one article for an assistant that reads it: both texts cut to a size a model can take,
 * the level data from the article list, and the words the article uses. None of the integer ids,
 * author details, engagement counts, hashtags or processing state the website's resources carry.
 *
 * Japanese text costs many tokens, and the website allows long articles, so the text is cut and
 * the answer says so with a flag per language instead of cutting silently.
 */
final class ArticleDetailPresenter
{
    /** Characters of each text, Japanese and English on their own, an assistant gets. */
    public const DEFAULT_MAX_TEXT_CHARACTERS = 10_000;

    /** A common word can list dozens of meanings; the first few are the ones to teach. */
    private const MEANINGS_PER_WORD = 3;

    public function __construct(private readonly int $maxTextCharacters = self::DEFAULT_MAX_TEXT_CHARACTERS)
    {
    }

    /**
     * @param array<int, Word> $words The words read for this answer, not necessarily all of the article's.
     * @param int $wordsTotal How many words the article has in all.
     *
     * @return array{
     *     uuid: string,
     *     title_jp: string,
     *     title_en: ?string,
     *     content_jp: string,
     *     content_en: ?string,
     *     content_jp_truncated: bool,
     *     content_en_truncated: bool,
     *     main_level: ?string,
     *     level_mix: array{N5: int, N4: int, N3: int, N2: int, N1: int, uncommon: int},
     *     source_link: string,
     *     words: array<int, array{word: string, reading: string, level: ?string, meaning: string}>,
     *     words_total: int,
     *     words_truncated: bool
     * }
     */
    public function present(Article $article, array $words, int $wordsTotal): array
    {
        $counts = $article->getJlptLevels();

        [$contentJp, $contentJpTruncated] = BoundedText::cut($article->getContentJp()->value, $this->maxTextCharacters);

        $contentEn = $article->getContentEn()?->value;
        [$contentEn, $contentEnTruncated] = $contentEn === null
            ? [null, false]
            : BoundedText::cut($contentEn, $this->maxTextCharacters);

        return [
            'uuid' => $article->getUid()->value(),
            'title_jp' => (string) $article->getTitleJp(),
            'title_en' => $article->getTitleEn()?->value,
            'content_jp' => $contentJp,
            'content_en' => $contentEn,
            'content_jp_truncated' => $contentJpTruncated,
            'content_en_truncated' => $contentEnTruncated,
            'main_level' => ArticleMainLevel::fromCounts($counts)?->value,
            'level_mix' => ArticleLevelMix::fromCounts($counts),
            'source_link' => $article->getSourceUrl()->value,
            'words' => array_map($this->presentWord(...), array_values($words)),
            'words_total' => $wordsTotal,
            'words_truncated' => $wordsTotal > count($words),
        ];
    }

    /**
     * @return array{word: string, reading: string, level: ?string, meaning: string}
     */
    private function presentWord(Word $word): array
    {
        return [
            'word' => $word->getSurface(),
            'reading' => $word->getFurigana(),
            'level' => StudyLevel::fromDictionary($word->getJlpt())?->value,
            'meaning' => implode('; ', array_slice($word->getMeanings(), 0, self::MEANINGS_PER_WORD)),
        ];
    }
}
