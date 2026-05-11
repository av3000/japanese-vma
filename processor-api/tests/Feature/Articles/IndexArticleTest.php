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

class IndexArticleTest extends TestCase
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
}
