<?php

namespace Tests\Feature\Articles;

use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ArticleWordsTest extends TestCase
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

    private function attachKanji(PersistenceArticle $article, string $character = '勉'): void
    {
        $kanjiId = DB::table('japanese_kanji_bank_long')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'kanji' => $character,
            'onyomi' => 'ベン',
            'kunyomi' => 'つと.める',
            'meaning' => 'exertion',
            'nanori' => '-',
            'grade' => '3',
            'stroke_count' => '10',
            'jlpt' => '3',
            'frequency' => '100',
            'radicals' => 'power',
            'radical_parts' => '力',
        ]);

        DB::table('article_kanji')->insert([
            'article_id' => $article->id,
            'kanji_id' => $kanjiId,
        ]);
    }

    public function test_words_page_returns_the_v1_list_envelope(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user);
        $this->attachWord($article);

        $this->json('GET', "/api/v1/articles/{$article->uuid}/words")
            ->assertStatus(200)
            ->assertJsonPath('items.0.word', '勉強')
            ->assertJsonPath('items.0.word_type', 'noun')
            ->assertJsonPath('items.0.word_k_ele', '勉強')
            ->assertJsonPath('items.0.furigana_r_ele', 'べんきょう')
            ->assertJsonPath('items.0.sense', 'study')
            ->assertJsonPath('pagination.page', 1)
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('pagination.has_more', false)
            ->assertJsonMissingPath('success')
            ->assertJsonMissingPath('words');
    }

    public function test_kanjis_page_returns_the_same_envelope(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user);
        $this->attachKanji($article);

        $this->json('GET', "/api/v1/articles/{$article->uuid}/kanjis")
            ->assertStatus(200)
            ->assertJsonPath('items.0.character', '勉')
            ->assertJsonPath('pagination.page', 1)
            ->assertJsonPath('pagination.per_page', 20)
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('pagination.last_page', 1)
            ->assertJsonPath('pagination.has_more', false);
    }

    public function test_pages_report_what_is_left_and_stay_stable_across_pages(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user);

        foreach (['勉強', '学校', '先生'] as $word) {
            $this->attachWord($article, $word);
        }

        $first = $this->json('GET', "/api/v1/articles/{$article->uuid}/words?per_page=2")
            ->assertStatus(200)
            ->assertJsonPath('pagination.total', 3)
            ->assertJsonPath('pagination.last_page', 2)
            ->assertJsonPath('pagination.has_more', true)
            ->assertJsonCount(2, 'items');

        $second = $this->json('GET', "/api/v1/articles/{$article->uuid}/words?per_page=2&page=2")
            ->assertStatus(200)
            ->assertJsonPath('pagination.page', 2)
            ->assertJsonPath('pagination.has_more', false)
            ->assertJsonCount(1, 'items');

        $firstPageIds = array_column($first->json('items'), 'id');
        $secondPageIds = array_column($second->json('items'), 'id');

        $this->assertSame([], array_intersect($firstPageIds, $secondPageIds), 'Pages must not overlap.');
    }

    public function test_pages_reject_a_per_page_beyond_the_ceiling(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user);

        $this->json('GET', "/api/v1/articles/{$article->uuid}/words?per_page=500")->assertStatus(422);
        $this->json('GET', "/api/v1/articles/{$article->uuid}/kanjis?page=0")->assertStatus(422);
    }

    public function test_pages_of_a_private_article_are_refused_to_a_stranger(): void
    {
        $owner = $this->createUser();
        $private = $this->createArticle($owner, ['publicity' => PublicityStatus::PRIVATE]);
        $this->attachWord($private);
        $this->attachKanji($private);

        $this->json('GET', "/api/v1/articles/{$private->uuid}/words")->assertStatus(403);
        $this->json('GET', "/api/v1/articles/{$private->uuid}/kanjis")->assertStatus(403);
    }

    public function test_the_owner_reads_the_pages_of_their_own_private_article(): void
    {
        $owner = $this->createUser();
        $private = $this->createArticle($owner, ['publicity' => PublicityStatus::PRIVATE]);
        $this->attachWord($private);
        $this->attachKanji($private);

        // The api guard resolves once per test process, so the principal has to be set before
        // the first request rather than part way through one test.
        Passport::actingAs($owner, ['*'], 'api');

        $this->json('GET', "/api/v1/articles/{$private->uuid}/words")
            ->assertStatus(200)
            ->assertJsonPath('items.0.word', '勉強');

        $this->json('GET', "/api/v1/articles/{$private->uuid}/kanjis")
            ->assertStatus(200)
            ->assertJsonPath('items.0.character', '勉');
    }

    public function test_unknown_article_is_a_404_on_both_pages(): void
    {
        $missing = (string) Str::uuid();

        $this->json('GET', "/api/v1/articles/{$missing}/words")->assertStatus(404);
        $this->json('GET', "/api/v1/articles/{$missing}/kanjis")->assertStatus(404);
    }

    public function test_a_numeric_id_no_longer_addresses_these_routes(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user);

        $this->json('GET', "/api/v1/articles/{$article->id}/words")->assertStatus(404);
    }
}
