<?php

namespace Tests\Feature\Articles;

use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\LastOperationStatus;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShowArticleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => UserRole::COMMON->value, 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => UserRole::ADMIN->value, 'guard_name' => 'api']);

        DB::table('objecttemplates')->insert([
            'id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'title' => 'article',
            'entity_type_uuid' => ObjectTemplateType::ARTICLE->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Test User',
            'email' => Str::uuid().'@example.com',
            'password' => Hash::make('password'),
            'uuid' => (string) Str::uuid(),
        ], $overrides));
    }

    private function createArticle(User $user, array $overrides = []): PersistenceArticle
    {
        return PersistenceArticle::create(array_merge([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'entity_type_uuid' => ObjectTemplateType::ARTICLE->value,
            'title_jp' => 'Japanese title',
            'title_en' => 'English title',
            'content_jp' => 'Japanese content text.',
            'content_en' => 'English content text.',
            'source_link' => 'https://example.com/source',
            'publicity' => PublicityStatus::PUBLIC,
            'status' => ArticleStatus::PENDING,
            'n1' => 0,
            'n2' => 0,
            'n3' => 0,
            'n4' => 0,
            'n5' => 0,
            'uncommon' => 0,
        ], $overrides));
    }

    private function attachKanji(PersistenceArticle $article, string $kanji = '水'): void
    {
        $kanjiId = DB::table('japanese_kanji_bank_long')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'kanji' => $kanji,
            'onyomi' => 'スイ',
            'kunyomi' => 'みず',
            'meaning' => 'water',
            'nanori' => '-',
            'grade' => '1',
            'stroke_count' => '4',
            'jlpt' => '5',
            'frequency' => '1',
            'radicals' => 'water',
            'radical_parts' => $kanji,
        ]);

        DB::table('article_kanji')->insert([
            'article_id' => $article->id,
            'kanji_id' => $kanjiId,
        ]);
    }

    private function attachWord(PersistenceArticle $article, string $word = '勉強'): void
    {
        $wordId = DB::table('japanese_word_bank_long')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'entry_sequence' => '1001',
            'word' => $word,
            'furigana' => 'べんきょう',
            'jlpt' => 'N5',
            'word_type' => 'noun',
            'word_k_ele' => $word,
            'furigana_r_ele' => 'べんきょう',
            'sense' => 'study',
        ]);

        DB::table('article_word')->insert([
            'article_id' => $article->id,
            'word_id' => $wordId,
        ]);
    }

    public function test_show_public_article_returns_flat_article_fields(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user);

        $response = $this->json('GET', "/api/v1/articles/{$article->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('uid', $article->uuid)
            ->assertJsonMissingPath('article');
    }

    public function test_show_returns_enriched_flat_article_detail_payload(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user, [
            'publicity' => PublicityStatus::PUBLIC,
        ]);

        $hashtagId = DB::table('uniquehashtags')->insertGetId([
            'content' => '#grammar',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('hashtag_entity')->insert([
            'entity_id' => $article->id,
            'entity_type_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'hashtag_id' => $hashtagId,
            'user_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('last_operations')->insert([
            'processable_id' => $article->uuid,
            'processable_type' => 'article',
            'task_type' => 'kanji_extraction',
            'status' => LastOperationStatus::COMPLETED->value,
            'metadata' => json_encode(['source' => 'test'], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->json('GET', "/api/v1/articles/{$article->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('uid', $article->uuid)
            ->assertJsonPath('hashtags.0.content', '#grammar')
            ->assertJsonPath('engagement.likes_count', 0)
            ->assertJsonPath('processing_status.type', 'kanji_extraction')
            ->assertJsonPath('processing_status.status', LastOperationStatus::COMPLETED->value)
            ->assertJsonMissingPath('article');
    }

    public function test_show_returns_words_by_default_when_article_has_attached_words(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user, [
            'publicity' => PublicityStatus::PUBLIC,
        ]);
        $this->attachWord($article, '勉強');

        $response = $this->json('GET', "/api/v1/articles/{$article->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('uid', $article->uuid)
            ->assertJsonPath('words.0.word', '勉強')
            ->assertJsonPath('words.0.word_type', 'noun')
            ->assertJsonPath('words.0.word_k_ele', '勉強')
            ->assertJsonPath('words.0.furigana_r_ele', 'べんきょう')
            ->assertJsonPath('words.0.sense', 'study')
            ->assertJsonMissingPath('article');
    }

    public function test_show_suppresses_words_when_include_words_is_false(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user, [
            'publicity' => PublicityStatus::PUBLIC,
        ]);
        $this->attachWord($article, '勉強');

        $response = $this->json('GET', "/api/v1/articles/{$article->uuid}?include_words=false");

        $response->assertStatus(200)
            ->assertJsonPath('uid', $article->uuid)
            ->assertJsonPath('words', []);
    }

    public function test_show_suppresses_kanjis_when_include_kanjis_is_false(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user, [
            'publicity' => PublicityStatus::PUBLIC,
        ]);
        $this->attachKanji($article, '水');

        $response = $this->json('GET', "/api/v1/articles/{$article->uuid}?include_kanjis=false");

        $response->assertStatus(200)
            ->assertJsonPath('uid', $article->uuid)
            ->assertJsonPath('kanjis', []);
    }

    public function test_show_returns_attached_kanjis_by_default(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user, [
            'publicity' => PublicityStatus::PUBLIC,
        ]);
        $this->attachKanji($article, '水');

        $this->getJson("/api/v1/articles/{$article->uuid}")
            ->assertOk()
            ->assertJsonPath('kanjis.0.character', '水');
    }
}
