<?php

declare(strict_types=1);

namespace Tests\Feature\JapaneseMaterial\Kanjis;

use App\Application\Articles\Services\ArticleServiceInterface;
use App\Application\JapaneseMaterial\Sentences\Services\SentenceServiceInterface;
use App\Application\JapaneseMaterial\Words\Services\WordServiceInterface;
use App\Domain\Articles\DTOs\ArticleListDTO;
use App\Domain\JapaneseMaterial\Sentences\Queries\SentenceQueryCriteria;
use App\Domain\JapaneseMaterial\Words\Queries\WordQueryCriteria;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KanjiRelatedQueryFiltersTest extends TestCase
{
    use RefreshDatabase;

    private int $relatedArticleId;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => UserRole::COMMON->value, 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => UserRole::ADMIN->value, 'guard_name' => 'api']);

        $this->createKanji(88, '水');
        $this->createKanji(89, '火');
        $this->createWord(101, '水泳');
        $this->createWord(102, '火山');
        $this->createSentence(201, '水を飲みます。');
        $this->createSentence(202, '火を消します。');

        DB::table('japanese_kanji_word_long')->insert([
            ['kanji_id' => 88, 'word_id' => 101],
            ['kanji_id' => 89, 'word_id' => 102],
        ]);
        DB::table('japanese_sentence_kanji')->insert([
            ['kanji_id' => 88, 'sentence_id' => 201],
            ['kanji_id' => 89, 'sentence_id' => 202],
        ]);

        $owner = $this->createUser();
        $related = $this->createArticle($owner, 301, PublicityStatus::PUBLIC, '水の記事');
        $unrelated = $this->createArticle($owner, 302, PublicityStatus::PUBLIC, '火の記事');
        $private = $this->createArticle($owner, 303, PublicityStatus::PRIVATE, '非公開の水の記事');
        $this->relatedArticleId = $related->id;

        DB::table('article_kanji')->insert([
            ['article_id' => $related->id, 'kanji_id' => 88],
            ['article_id' => $unrelated->id, 'kanji_id' => 89],
            ['article_id' => $private->id, 'kanji_id' => 88],
        ]);
    }

    public function test_word_and_sentence_queries_filter_by_kanji_id(): void
    {
        $wordResult = app(WordServiceInterface::class)->find(
            WordQueryCriteria::forListing(kanjiId: 88),
        );
        $sentenceResult = app(SentenceServiceInterface::class)->find(
            SentenceQueryCriteria::forListing(kanjiId: 88),
        );

        $this->assertSame([101], array_map(
            static fn ($word): int => $word->getIdValue(),
            $wordResult->getData()->items,
        ));

        $this->assertSame([201], array_map(
            static fn ($sentence): int => $sentence->getIdValue(),
            $sentenceResult->getData()->items,
        ));
    }

    public function test_article_query_filters_by_kanji_id_and_keeps_visibility_rules(): void
    {
        $result = app(ArticleServiceInterface::class)->getArticlesList(
            new ArticleListDTO(
                category: null,
                search: null,
                author_uid: null,
                sort_by: 'created_at',
                sort_dir: 'desc',
                per_page: 5,
                page: 1,
                include_stats_counts: false,
                include_hashtags: false,
                include_kanjis: false,
                include_words: false,
                kanji_id: 88,
            ),
            null,
        );

        $this->assertCount(1, $result->items);
        $this->assertSame($this->relatedArticleId, $result->items[0]->article->getIdValue());
    }

    private function createKanji(int $id, string $kanji): void
    {
        DB::table('japanese_kanji_bank_long')->insert([
            'id' => $id,
            'uuid' => (string) Str::uuid(),
            'kanji' => $kanji,
            'onyomi' => 'スイ',
            'kunyomi' => 'みず',
            'meaning' => 'meaning',
            'nanori' => '',
            'grade' => '1',
            'stroke_count' => '4',
            'jlpt' => '5',
            'frequency' => '2',
            'radicals' => $kanji,
            'radical_parts' => $kanji,
        ]);
    }

    private function createWord(int $id, string $word): void
    {
        DB::table('japanese_word_bank_long')->insert([
            'id' => $id,
            'uuid' => (string) Str::uuid(),
            'entry_sequence' => (string) (1000 + $id),
            'word' => $word,
            'furigana' => 'ふりがな',
            'jlpt' => 'N5',
            'word_type' => 'noun',
            'word_k_ele' => $word,
            'furigana_r_ele' => 'ふりがな',
            'sense' => json_encode([[['gloss', ['meaning']]]], JSON_THROW_ON_ERROR),
        ]);
    }

    private function createSentence(int $id, string $content): void
    {
        DB::table('japanese_tatoeba_sentences')->insert([
            'id' => $id,
            'uuid' => (string) Str::uuid(),
            'user_id' => null,
            'tatoeba_entry' => (string) (2000 + $id),
            'content' => $content,
        ]);
    }

    private function createUser(): User
    {
        return User::create([
            'name' => 'Article Owner',
            'email' => Str::uuid().'@example.com',
            'password' => Hash::make('password'),
            'uuid' => (string) Str::uuid(),
        ]);
    }

    private function createArticle(
        User $user,
        int $id,
        PublicityStatus $publicity,
        string $title,
    ): Article {
        return Article::create([
            'id' => $id,
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'entity_type_uuid' => ObjectTemplateType::ARTICLE->value,
            'title_jp' => $title,
            'title_en' => $title,
            'content_jp' => 'Japanese content text.',
            'content_en' => 'English content text.',
            'source_link' => 'https://example.com/source',
            'publicity' => $publicity,
            'status' => ArticleStatus::PENDING,
            'n1' => 0,
            'n2' => 0,
            'n3' => 0,
            'n4' => 0,
            'n5' => 0,
            'uncommon' => 0,
        ]);
    }
}
