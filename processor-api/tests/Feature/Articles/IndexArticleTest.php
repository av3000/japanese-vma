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
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

class IndexArticleTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
    }

    private function createUser(array $overrides = []): User
    {
        return User::factory()->create($overrides);
    }

    private function createArticle(User $user, array $overrides = []): PersistenceArticle
    {
        return PersistenceArticle::factory()
            ->byUser($user)
            ->create(array_merge([
                'publicity' => PublicityStatus::PUBLIC,
                'status' => ArticleStatus::PENDING,
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

    private function assertArticleTitles(array $items, array $expectedTitles): void
    {
        $actualTitles = array_column($items, 'title_jp');
        sort($actualTitles);
        sort($expectedTitles);

        $this->assertSame($expectedTitles, $actualTitles);
    }

    public function test_index_filters_articles_by_author_uid_for_authenticated_owner(): void
    {
        $owner = $this->createUser();
        $otherUser = $this->createUser();

        $this->createArticle($owner, [
            'title_jp' => 'Owner Public',
            'publicity' => PublicityStatus::PUBLIC,
        ]);
        $this->createArticle($owner, [
            'title_jp' => 'Owner Private',
            'publicity' => PublicityStatus::PRIVATE,
        ]);
        $this->createArticle($otherUser, [
            'title_jp' => 'Other Public',
            'publicity' => PublicityStatus::PUBLIC,
        ]);

        Passport::actingAs($owner, ['*'], 'api');

        $response = $this
            ->withHeader('Authorization', 'Bearer test-token')
            ->json('GET', '/api/v1/articles', [
                'author_uid' => $owner->uuid,
            ]);

        $response->assertStatus(200);
        $this->assertArticleTitles($response->json('items'), ['Owner Private', 'Owner Public']);
    }

    public function test_index_filters_other_authors_private_articles_for_authenticated_non_admin(): void
    {
        $viewer = $this->createUser();
        $author = $this->createUser();

        $this->createArticle($author, [
            'title_jp' => 'Author Public',
            'publicity' => PublicityStatus::PUBLIC,
        ]);
        $this->createArticle($author, [
            'title_jp' => 'Author Private',
            'publicity' => PublicityStatus::PRIVATE,
        ]);

        Passport::actingAs($viewer, ['*'], 'api');

        $response = $this
            ->withHeader('Authorization', 'Bearer test-token')
            ->json('GET', '/api/v1/articles', [
                'author_uid' => $author->uuid,
            ]);

        $response->assertStatus(200);
        $this->assertArticleTitles($response->json('items'), ['Author Public']);
    }

    public function test_index_filters_other_authors_private_articles_for_anonymous_user(): void
    {
        $author = $this->createUser();

        $this->createArticle($author, [
            'title_jp' => 'Anonymous Public',
            'publicity' => PublicityStatus::PUBLIC,
        ]);
        $this->createArticle($author, [
            'title_jp' => 'Anonymous Private',
            'publicity' => PublicityStatus::PRIVATE,
        ]);

        $response = $this->json('GET', '/api/v1/articles', [
            'author_uid' => $author->uuid,
        ]);

        $response->assertStatus(200);
        $this->assertArticleTitles($response->json('items'), ['Anonymous Public']);
    }

    public function test_index_returns_all_matching_author_articles_for_admin(): void
    {
        $admin = $this->createUser();
        $admin->assignRole(UserRole::ADMIN->value);
        $author = $this->createUser();

        $this->createArticle($author, [
            'title_jp' => 'Admin Public',
            'publicity' => PublicityStatus::PUBLIC,
        ]);
        $this->createArticle($author, [
            'title_jp' => 'Admin Private',
            'publicity' => PublicityStatus::PRIVATE,
        ]);

        Passport::actingAs($admin, ['*'], 'api');

        $response = $this
            ->withHeader('Authorization', 'Bearer test-token')
            ->json('GET', '/api/v1/articles', [
                'author_uid' => $author->uuid,
            ]);

        $response->assertStatus(200);
        $this->assertArticleTitles($response->json('items'), ['Admin Private', 'Admin Public']);
    }

    public function test_index_rejects_invalid_author_uid(): void
    {
        $response = $this->json('GET', '/api/v1/articles', [
            'author_uid' => 'not-a-uuid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['author_uid']);
    }

    public function test_index_returns_attached_kanjis(): void
    {
        $author = $this->createUser();
        $article = $this->createArticle($author);
        $this->attachKanji($article);

        $this->getJson('/api/v1/articles')
            ->assertOk()
            ->assertJsonPath('items.0.kanjis.0.character', '水');
    }

    public function test_index_returns_enriched_article_list_payload(): void
    {
        $author = $this->createUser();
        $article = $this->createArticle($author, [
            'title_jp' => 'Tagged Article',
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
            'user_id' => $author->id,
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

        $response = $this->json('GET', '/api/v1/articles');

        $response->assertStatus(200)
            ->assertJsonPath('items.0.hashtags.0.content', '#grammar')
            ->assertJsonPath('items.0.engagement.stats.likes_count', 0)
            ->assertJsonPath('items.0.processing_status.type', 'kanji_extraction')
            ->assertJsonPath('items.0.processing_status.status', LastOperationStatus::COMPLETED->value);
    }

    public function test_index_suppresses_stats_when_include_stats_counts_is_false(): void
    {
        $author = $this->createUser();
        $this->createArticle($author, [
            'title_jp' => 'No Stats Article',
            'publicity' => PublicityStatus::PUBLIC,
        ]);

        $response = $this->json('GET', '/api/v1/articles', [
            'include_stats_counts' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('items.0.engagement.stats', null);
    }
}
