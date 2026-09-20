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
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ArticleAttachmentFilterTest extends TestCase
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

    private function attachWord(PersistenceArticle $article, string $word = '勉強', string $jlpt = 'N5'): void
    {
        $wordId = DB::table('japanese_word_bank_long')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'entry_sequence' => '1001',
            'word' => $word,
            'furigana' => 'べんきょう',
            'jlpt' => $jlpt,
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

    public function test_the_word_index_returns_only_the_words_attached_to_the_article(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user);
        $other = $this->createArticle($user);
        $this->attachWord($article, '勉強');
        $this->attachWord($other, '学校');

        $this->json('GET', "/api/v1/words?article_uuid={$article->uuid}")
            ->assertStatus(200)
            ->assertJsonPath('items.0.word', '勉強')
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('pagination.total', 1);
    }

    public function test_the_kanji_index_returns_only_the_kanji_attached_to_the_article(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user);
        $other = $this->createArticle($user);
        $this->attachKanji($article, '勉');
        $this->attachKanji($other, '水');

        $this->json('GET', "/api/v1/kanjis?article_uuid={$article->uuid}")
            ->assertStatus(200)
            ->assertJsonPath('items.0.character', '勉')
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('pagination.total', 1);
    }

    public function test_the_article_filter_pages_like_every_other_v1_list(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user);

        foreach (['勉強', '学校', '先生'] as $word) {
            $this->attachWord($article, $word);
        }

        $first = $this->json('GET', "/api/v1/words?article_uuid={$article->uuid}&per_page=2")
            ->assertStatus(200)
            ->assertJsonPath('pagination.total', 3)
            ->assertJsonPath('pagination.last_page', 2)
            ->assertJsonPath('pagination.has_more', true)
            ->assertJsonCount(2, 'items');

        $second = $this->json('GET', "/api/v1/words?article_uuid={$article->uuid}&per_page=2&page=2")
            ->assertStatus(200)
            ->assertJsonPath('pagination.page', 2)
            ->assertJsonPath('pagination.has_more', false)
            ->assertJsonCount(1, 'items');

        $this->assertSame(
            [],
            array_intersect(array_column($first->json('items'), 'id'), array_column($second->json('items'), 'id')),
            'Pages must not overlap.',
        );
    }

    public function test_the_article_filter_combines_with_the_other_list_filters(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user);
        $this->attachWord($article, '勉強');
        $this->attachWord($article, '学校', jlpt: 'N4');

        $this->json('GET', "/api/v1/words?article_uuid={$article->uuid}&jlpt=N4")
            ->assertStatus(200)
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.word', '学校');
    }

    public function test_an_unknown_article_uuid_returns_an_empty_page_rather_than_an_error(): void
    {
        // The filter narrows a public catalogue of words; an article nobody can see simply has
        // no words in it, which is not the same thing as a bad request.
        $this->json('GET', '/api/v1/words?article_uuid='.Str::uuid())
            ->assertStatus(200)
            ->assertJsonCount(0, 'items')
            ->assertJsonPath('pagination.total', 0);
    }

    public function test_a_malformed_article_uuid_is_rejected(): void
    {
        $this->json('GET', '/api/v1/words?article_uuid=not-a-uuid')->assertStatus(422);
        $this->json('GET', '/api/v1/kanjis?article_uuid=not-a-uuid')->assertStatus(422);
    }

    public function test_the_article_scoped_routes_are_gone(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user);

        // #268 settled on one query path per resource: the resource's own index, filtered.
        $this->json('GET', "/api/v1/articles/{$article->uuid}/words")->assertStatus(404);
        $this->json('GET', "/api/v1/articles/{$article->uuid}/kanjis")->assertStatus(404);
    }
}
