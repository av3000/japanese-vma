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

class DestroyArticleTest extends TestCase
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
            'publicity' => PublicityStatus::PRIVATE,
            'status' => ArticleStatus::PENDING,
            'n1' => 0,
            'n2' => 0,
            'n3' => 0,
            'n4' => 0,
            'n5' => 0,
            'uncommon' => 0,
        ], $overrides));
    }

    public function test_destroy_owner_gets_legacy_success_body_and_article_is_deleted(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user);

        Passport::actingAs($user, ['*'], 'api');

        $this->json('DELETE', "/api/v1/articles/{$article->uuid}")
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Article deleted successfully',
            ]);

        $this->assertDatabaseMissing('articles', [
            'id' => $article->id,
        ]);
    }

    public function test_destroy_unknown_uuid_returns_not_found_legacy_error_body(): void
    {
        $user = $this->createUser();

        Passport::actingAs($user, ['*'], 'api');

        $this->json('DELETE', '/api/v1/articles/'.(string) Str::uuid())
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_destroy_non_owner_returns_forbidden_legacy_error_body(): void
    {
        $owner = $this->createUser();
        $otherUser = $this->createUser();
        $article = $this->createArticle($owner);

        Passport::actingAs($otherUser, ['*'], 'api');

        $this->json('DELETE', "/api/v1/articles/{$article->uuid}")
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }
}
