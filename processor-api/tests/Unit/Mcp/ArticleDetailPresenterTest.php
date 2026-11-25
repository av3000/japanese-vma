<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp;

use App\Domain\Articles\Models\Article;
use App\Domain\Articles\ValueObjects\ArticleContent;
use App\Domain\Articles\ValueObjects\ArticleSourceUrl;
use App\Domain\Articles\ValueObjects\ArticleTitle;
use App\Domain\JapaneseMaterial\Words\Models\Word;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\JlptLevels;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;
use App\Mcp\Presenters\ArticleDetailPresenter;
use PHPUnit\Framework\TestCase;

final class ArticleDetailPresenterTest extends TestCase
{
    public function test_the_article_is_shaped_with_exactly_the_documented_keys(): void
    {
        $uuid = EntityId::generate();
        $article = $this->article(uuid: $uuid, levels: new JlptLevels(n1: 0, n2: 0, n3: 1, n4: 3, n5: 12, uncommon: 1));

        $output = (new ArticleDetailPresenter())->present($article, [$this->word('学校', 'がっこう', '5', ['school', 'academy'])], 1);

        $this->assertSame([
            'uuid', 'title_jp', 'title_en', 'content_jp', 'content_en', 'content_jp_truncated', 'content_en_truncated',
            'main_level', 'level_mix', 'source_link', 'words', 'words_total', 'words_truncated',
        ], array_keys($output));
        $this->assertSame($uuid->value(), $output['uuid']);
        $this->assertSame('日本の食べ物', $output['title_jp']);
        $this->assertSame('Food in Japan', $output['title_en']);
        $this->assertSame('日本の食べ物についての短い文章です。', $output['content_jp']);
        $this->assertSame('A short text about food in Japan.', $output['content_en']);
        $this->assertSame('N3', $output['main_level']);
        $this->assertSame(['N5' => 12, 'N4' => 3, 'N3' => 1, 'N2' => 0, 'N1' => 0, 'uncommon' => 1], $output['level_mix']);
        $this->assertSame('https://example.com/food', $output['source_link']);
        $this->assertSame(1, $output['words_total']);
        $this->assertFalse($output['words_truncated']);
    }

    public function test_an_article_without_an_english_title_or_text_has_nulls_and_untruncated_flags(): void
    {
        $output = (new ArticleDetailPresenter())->present($this->article(withEnglish: false), [], 0);

        $this->assertNull($output['title_en']);
        $this->assertNull($output['content_en']);
        $this->assertFalse($output['content_en_truncated']);
        $this->assertNull($output['main_level']);
        $this->assertSame([], $output['words']);
        $this->assertSame(0, $output['words_total']);
        $this->assertFalse($output['words_truncated']);
    }

    public function test_text_over_the_limit_is_cut_and_flagged_for_each_language_on_its_own(): void
    {
        $presenter = new ArticleDetailPresenter(maxTextCharacters: 10);

        $output = $presenter->present($this->article(contentJp: str_repeat('あ', 40), contentEn: 'Short one.'), [], 0);

        $this->assertSame(str_repeat('あ', 10), $output['content_jp']);
        $this->assertTrue($output['content_jp_truncated']);
        $this->assertSame('Short one.', $output['content_en']);
        $this->assertFalse($output['content_en_truncated']);

        $output = $presenter->present($this->article(contentJp: 'ここは短い文章です。', contentEn: str_repeat('x', 40)), [], 0);

        $this->assertSame('ここは短い文章です。', $output['content_jp']);
        $this->assertFalse($output['content_jp_truncated']);
        $this->assertSame(str_repeat('x', 10), $output['content_en']);
        $this->assertTrue($output['content_en_truncated']);
    }

    public function test_the_default_limit_is_ten_thousand_characters(): void
    {
        $this->assertSame(10_000, ArticleDetailPresenter::DEFAULT_MAX_TEXT_CHARACTERS);
    }

    public function test_a_word_has_its_headword_reading_level_and_the_first_three_meanings(): void
    {
        $words = [
            $this->word('学校', 'がっこう', '5', ['school', 'academy', 'educational institution', 'a fourth meaning']),
            $this->word('勉強', 'べんきょう', '-', ['study']),
            $this->word('無い', 'ない', '-', []),
        ];

        $output = (new ArticleDetailPresenter())->present($this->article(), $words, 3);

        $this->assertSame([
            ['word' => '学校', 'reading' => 'がっこう', 'level' => 'N5', 'meaning' => 'school; academy; educational institution'],
            ['word' => '勉強', 'reading' => 'べんきょう', 'level' => null, 'meaning' => 'study'],
            ['word' => '無い', 'reading' => 'ない', 'level' => null, 'meaning' => ''],
        ], $output['words']);
    }

    public function test_the_word_list_is_truncated_when_the_total_is_larger_than_the_words_given(): void
    {
        $output = (new ArticleDetailPresenter())->present($this->article(), [$this->word('学校', 'がっこう', '-', ['school'])], 7);

        $this->assertSame(7, $output['words_total']);
        $this->assertTrue($output['words_truncated']);
    }

    /**
     * @param array<int, string> $meanings
     */
    private function word(string $surface, string $reading, string $jlpt, array $meanings): Word
    {
        return new Word(1, EntityId::generate(), $surface, $reading, $jlpt, ['noun'], [$surface], [$reading], $meanings, null, null, null, null);
    }

    private function article(
        ?EntityId $uuid = null,
        ?JlptLevels $levels = null,
        bool $withEnglish = true,
        string $contentJp = '日本の食べ物についての短い文章です。',
        string $contentEn = 'A short text about food in Japan.',
    ): Article {
        return new Article(
            1,
            $uuid ?? EntityId::generate(),
            'article',
            new UserId(1),
            new UserName('Author'),
            EntityId::generate(),
            new ArticleTitle('日本の食べ物'),
            $withEnglish ? new ArticleTitle('Food in Japan') : null,
            new ArticleContent($contentJp),
            $withEnglish ? new ArticleContent($contentEn) : null,
            new ArticleSourceUrl('https://example.com/food'),
            PublicityStatus::PUBLIC,
            ArticleStatus::APPROVED,
            $levels ?? JlptLevels::empty(),
            new \DateTimeImmutable('2026-01-01'),
            new \DateTimeImmutable('2026-01-02'),
        );
    }
}
