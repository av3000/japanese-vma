<?php

declare(strict_types=1);

namespace Tests\Feature\JapaneseMaterial\Kanjis;

use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KanjiV1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => UserRole::COMMON->value, 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => UserRole::ADMIN->value, 'guard_name' => 'api']);
    }

    public function test_guest_can_list_kanjis_with_pagination(): void
    {
        $firstUuid = (string) Str::uuid();
        $secondUuid = (string) Str::uuid();

        $this->createKanji(id: 1, uuid: $firstUuid, kanji: '水', meaning: 'water|river', jlpt: '5', strokeCount: '4');
        $this->createKanji(id: 2, uuid: $secondUuid, kanji: '火', meaning: 'fire|flame', jlpt: '5', strokeCount: '4');

        $response = $this->getJson('/api/v1/kanjis?per_page=1');

        $response->assertOk()
            ->assertJsonMissingPath('success')
            ->assertJsonMissingPath('data')
            ->assertJsonPath('items.0.id', 1)
            ->assertJsonPath('items.0.uuid', $firstUuid)
            ->assertJsonPath('items.0.character', '水')
            ->assertJsonPath('items.0.meanings.0', 'water')
            ->assertJsonPath('items.0.meanings.1', 'river')
            ->assertJsonPath('items.0.onyomi.0', 'スイ')
            ->assertJsonPath('items.0.kunyomi.0', 'みず')
            ->assertJsonPath('items.0.stroke_count', 4)
            ->assertJsonPath('items.0.jlpt', '5')
            ->assertJsonPath('items.0.frequency', 2)
            ->assertJsonPath('pagination.page', 1)
            ->assertJsonPath('pagination.per_page', 1)
            ->assertJsonPath('pagination.total', 2)
            ->assertJsonPath('pagination.has_more', true);
    }

    public function test_guest_can_filter_kanjis_by_keyword_and_jlpt(): void
    {
        $this->createKanji(id: 1, kanji: '水', meaning: 'water|river', jlpt: '5');
        $this->createKanji(id: 2, kanji: '火', meaning: 'fire|flame', jlpt: '4');
        $this->createKanji(id: 3, kanji: '川', meaning: 'river|stream', jlpt: '5');

        $response = $this->getJson('/api/v1/kanjis?keyword=river&jlpt=5');

        $response->assertOk()
            ->assertJsonCount(2, 'items')
            ->assertJsonPath('items.0.character', '水')
            ->assertJsonPath('items.1.character', '川')
            ->assertJsonPath('pagination.total', 2);
    }

    public function test_guest_can_filter_kanjis_by_stroke_range(): void
    {
        $this->createKanji(id: 1, kanji: '一', meaning: 'one', strokeCount: '1');
        $this->createKanji(id: 2, kanji: '水', meaning: 'water', strokeCount: '4');
        $this->createKanji(id: 3, kanji: '語', meaning: 'language', strokeCount: '14');

        $response = $this->getJson('/api/v1/kanjis?min_stroke_count=4&max_stroke_count=14');

        $response->assertOk()
            ->assertJsonCount(2, 'items')
            ->assertJsonPath('items.0.character', '水')
            ->assertJsonPath('items.1.character', '語')
            ->assertJsonPath('pagination.total', 2);
    }

    public function test_guest_can_fetch_kanji_detail_by_uuid(): void
    {
        $uuid = (string) Str::uuid();

        $this->createKanji(id: 10, uuid: $uuid, kanji: '水', meaning: 'water|river', jlpt: '5');

        $response = $this->getJson("/api/v1/kanjis/{$uuid}");

        $response->assertOk()
            ->assertJsonMissingPath('success')
            ->assertJsonMissingPath('data')
            ->assertJsonPath('id', 10)
            ->assertJsonPath('uuid', $uuid)
            ->assertJsonPath('character', '水')
            ->assertJsonPath('meanings.0', 'water')
            ->assertJsonPath('meanings.1', 'river')
            ->assertJsonPath('jlpt', '5');
    }

    public function test_guest_can_fetch_kanji_detail_by_character(): void
    {
        $uuid = (string) Str::uuid();

        $this->createKanji(id: 20, uuid: $uuid, kanji: '火', meaning: 'fire', jlpt: '4');

        $response = $this->getJson('/api/v1/kanjis/'.rawurlencode('火'));

        $response->assertOk()
            ->assertJsonPath('id', 20)
            ->assertJsonPath('uuid', $uuid)
            ->assertJsonPath('character', '火')
            ->assertJsonPath('meanings.0', 'fire');
    }

    public function test_guest_can_fetch_kanji_detail_by_legacy_numeric_id(): void
    {
        $uuid = (string) Str::uuid();

        $this->createKanji(id: 77, uuid: $uuid, kanji: '水', meaning: 'water');

        $response = $this->getJson('/api/v1/kanjis/77');

        $response->assertOk()
            ->assertJsonPath('id', 77)
            ->assertJsonPath('uuid', $uuid)
            ->assertJsonPath('character', '水');
    }

    public function test_invalid_kanji_identifier_returns_bad_request_problem_response(): void
    {
        $response = $this->getJson('/api/v1/kanjis/not-a-valid-identifier');

        $response->assertBadRequest()
            ->assertJsonPath('status', 400)
            ->assertJsonPath(
                'title',
                'Identifier must be a valid UUID, positive numeric Kanji ID, or a single Kanji character.',
            );
    }

    public function test_kanji_list_rejects_invalid_per_page(): void
    {
        $response = $this->getJson('/api/v1/kanjis?per_page=101');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    public function test_guest_kanji_list_includes_null_viewer_catalogue_state(): void
    {
        $this->createKanji(id: 101, kanji: '猫', meaning: 'cat');

        $response = $this->getJson('/api/v1/kanjis?per_page=1&include=viewer_catalogue_state');

        $response->assertOk()
            ->assertJsonStructure(['items' => [['viewer_catalogue_state']]])
            ->assertJsonPath('items.0.id', 101)
            ->assertJsonPath('items.0.viewer_catalogue_state', null);
    }

    public function test_authenticated_kanji_list_batches_saved_and_known_viewer_catalogue_state(): void
    {
        $user = $this->createUser();
        $this->createKanji(id: 101, kanji: '猫', meaning: 'cat');
        $this->createKanji(id: 102, kanji: '犬', meaning: 'dog');

        $savedListId = $this->createCatalogue($user->id, SavedListType::KANJIS, 'Saved Kanjis');
        $knownListId = $this->createCatalogue($user->id, SavedListType::KNOWNKANJIS, 'Known Kanjis');
        $this->attachCatalogueItem($savedListId, SavedListType::KANJIS, 101);
        $this->attachCatalogueItem($knownListId, SavedListType::KNOWNKANJIS, 102);

        Passport::actingAs($user, ['*'], 'api');

        $response = $this->getJson('/api/v1/kanjis?per_page=2&include=viewer_catalogue_state');

        $response->assertOk()
            ->assertJsonPath('items.0.viewer_catalogue_state.is_saved', true)
            ->assertJsonPath('items.0.viewer_catalogue_state.is_known', false)
            ->assertJsonPath('items.1.viewer_catalogue_state.is_saved', false)
            ->assertJsonPath('items.1.viewer_catalogue_state.is_known', true);
    }

    public function test_authenticated_kanji_detail_includes_viewer_catalogue_state(): void
    {
        $user = $this->createUser();
        $uuid = (string) Str::uuid();
        $this->createKanji(id: 110, uuid: $uuid, kanji: '猫', meaning: 'cat');

        $savedListId = $this->createCatalogue($user->id, SavedListType::KANJIS, 'Saved Kanjis');
        $this->attachCatalogueItem($savedListId, SavedListType::KANJIS, 110);

        Passport::actingAs($user, ['*'], 'api');

        $response = $this->getJson(
            "/api/v1/kanjis/{$uuid}?include=viewer_catalogue_state",
        );

        $response->assertOk()
            ->assertJsonPath('viewer_catalogue_state.is_saved', true)
            ->assertJsonPath('viewer_catalogue_state.is_known', false);
    }

    public function test_lean_kanji_detail_omits_related_collections(): void
    {
        $uuid = $this->createRelatedKanjiFixture();

        $response = $this->getJson("/api/v1/kanjis/{$uuid}");

        $response->assertOk()
            ->assertJsonMissingPath('words')
            ->assertJsonMissingPath('sentences')
            ->assertJsonMissingPath('articles');
    }

    public function test_kanji_detail_includes_related_words_sentences_and_visible_articles(): void
    {
        $uuid = $this->createRelatedKanjiFixture();

        $response = $this->getJson(
            "/api/v1/kanjis/{$uuid}?include=words,sentences,articles",
        );

        $response->assertOk()
            ->assertJsonPath('words.items.0.word', '水泳')
            ->assertJsonPath('words.pagination.total', 1)
            ->assertJsonPath('sentences.items.0.content', '水を飲みます。')
            ->assertJsonPath('sentences.pagination.total', 1)
            ->assertJsonCount(1, 'articles')
            ->assertJsonPath('articles.0.title_jp', '水の記事')
            ->assertJsonPath('articles.0.hashtags', [])
            ->assertJsonPath('articles.0.views_total', 0)
            ->assertJsonPath('articles.0.likes_total', 0)
            ->assertJsonPath('articles.0.comments_total', 0)
            ->assertJsonMissingPath('articles.items')
            ->assertJsonMissingPath('articles.pagination')
            ->assertJsonMissingPath('articles.0.content_jp')
            ->assertJsonMissingPath('articles.0.engagement');
    }

    public function test_kanji_detail_rejects_unknown_include_values(): void
    {
        $uuid = $this->createRelatedKanjiFixture();

        $this->getJson("/api/v1/kanjis/{$uuid}?include=words,unknown")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['include']);
    }

    private function createUser(): User
    {
        return User::create([
            'name' => 'Test User',
            'email' => Str::uuid().'@example.com',
            'password' => Hash::make('password'),
            'uuid' => (string) Str::uuid(),
        ]);
    }

    private function createCatalogue(int $userId, SavedListType $type, string $title): int
    {
        return DB::table('customlists')->insertGetId([
            'user_id' => $userId,
            'uuid' => (string) Str::uuid(),
            'type' => $type->value,
            'title' => $title,
            'description' => '',
            'publicity' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function attachCatalogueItem(int $listId, SavedListType $type, int $kanjiId): void
    {
        DB::table('customlist_object')->insert([
            'list_id' => $listId,
            'listtype_id' => (string) $type->value,
            'real_object_id' => $kanjiId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createRelatedKanjiFixture(): string
    {
        $uuid = (string) Str::uuid();
        $this->createKanji(id: 88, uuid: $uuid, kanji: '水', meaning: 'water');
        $this->createKanji(id: 89, kanji: '火', meaning: 'fire');

        $this->createWord(id: 501, word: '水泳', furigana: 'すいえい');
        $this->createWord(id: 502, word: '火山', furigana: 'かざん');
        DB::table('japanese_kanji_word_long')->insert([
            ['kanji_id' => 88, 'word_id' => 501],
            ['kanji_id' => 89, 'word_id' => 502],
        ]);

        $this->createSentence(id: 601, content: '水を飲みます。');
        $this->createSentence(id: 602, content: '火を消します。');
        DB::table('japanese_sentence_kanji')->insert([
            ['kanji_id' => 88, 'sentence_id' => 601],
            ['kanji_id' => 89, 'sentence_id' => 602],
        ]);

        $owner = $this->createUser();
        $publicArticle = $this->createArticle($owner, '水の記事', PublicityStatus::PUBLIC);
        $privateArticle = $this->createArticle($owner, '非公開の水の記事', PublicityStatus::PRIVATE);
        DB::table('article_kanji')->insert([
            ['article_id' => $publicArticle->id, 'kanji_id' => 88],
            ['article_id' => $privateArticle->id, 'kanji_id' => 88],
        ]);

        return $uuid;
    }

    private function createWord(int $id, string $word, string $furigana): void
    {
        DB::table('japanese_word_bank_long')->insert([
            'id' => $id,
            'uuid' => (string) Str::uuid(),
            'entry_sequence' => (string) (1000 + $id),
            'word' => $word,
            'furigana' => $furigana,
            'jlpt' => 'N5',
            'word_type' => 'noun',
            'word_k_ele' => $word,
            'furigana_r_ele' => $furigana,
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

    private function createArticle(User $user, string $title, PublicityStatus $publicity): Article
    {
        return Article::create([
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

    private function createKanji(
        int $id,
        ?string $uuid = null,
        string $kanji = '水',
        string $onyomi = 'スイ',
        string $kunyomi = 'みず',
        string $meaning = 'water',
        string $nanori = '',
        string $grade = '1',
        string $strokeCount = '4',
        string $jlpt = '5',
        string $frequency = '2',
        string $radicals = '水',
        string $radicalParts = '水',
    ): void {
        DB::table('japanese_kanji_bank_long')->insert([
            'id' => $id,
            'uuid' => $uuid ?? (string) Str::uuid(),
            'kanji' => $kanji,
            'onyomi' => $onyomi,
            'kunyomi' => $kunyomi,
            'meaning' => $meaning,
            'nanori' => $nanori,
            'grade' => $grade,
            'stroke_count' => $strokeCount,
            'jlpt' => $jlpt,
            'frequency' => $frequency,
            'radicals' => $radicals,
            'radical_parts' => $radicalParts,
        ]);
    }
}
