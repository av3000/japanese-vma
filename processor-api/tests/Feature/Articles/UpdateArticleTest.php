<?php

namespace Tests\Feature\Articles;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;
use App\Application\Articles\Jobs\ProcessArticleKanjisJob;
use App\Domain\Shared\Enums\{ArticleStatus, ObjectTemplateType, PublicityStatus, UserRole};
use App\Infrastructure\Persistence\Models\{Article as PersistenceArticle, User, HashtagEntity};

class UpdateArticleTest extends TestCase
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
            'email' => Str::uuid() . '@example.com',
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

    private function getHashtagContents(PersistenceArticle $article): array
    {
        return HashtagEntity::with('uniquehashtag')
            ->where('entity_id', $article->id)
            ->get()
            ->map(fn ($link) => $link->uniquehashtag->content)
            ->values()
            ->all();
    }

    public function test_update_title_only(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user);

        Passport::actingAs($user, ['*'], 'api');

        $response = $this->json('PUT', "/api/v1/articles/{$article->uuid}", [
            'title_jp' => 'Updated title'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title_jp', 'Updated title');
    }

    public function test_update_empty_payload_returns_validation_error(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user);

        Passport::actingAs($user, ['*'], 'api');

        $response = $this->json('PUT', "/api/v1/articles/{$article->uuid}", []);

        $response->assertStatus(422)
            ->assertJsonPath('errors.fields.0', 'At least one field must be provided for update operation');
    }

    public function test_update_non_owner_returns_forbidden(): void
    {
        $owner = $this->createUser();
        $otherUser = $this->createUser(['email' => Str::uuid() . '@example.com']);
        $article = $this->createArticle($owner);

        Passport::actingAs($otherUser, ['*'], 'api');

        $response = $this->json('PUT', "/api/v1/articles/{$article->uuid}", [
            'title_jp' => 'Attempted update'
        ]);

        $response->assertStatus(403);
    }

    public function test_update_unknown_uuid_returns_not_found(): void
    {
        $user = $this->createUser();

        Passport::actingAs($user, ['*'], 'api');

        $response = $this->json('PUT', '/api/v1/articles/' . (string) Str::uuid(), [
            'title_jp' => 'Updated title'
        ]);

        $response->assertStatus(404);
    }

    public function test_update_hashtags_replaces_existing(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user);

        Passport::actingAs($user, ['*'], 'api');

        $this->json('PUT', "/api/v1/articles/{$article->uuid}", [
            'hashtags' => ['#old']
        ])->assertStatus(200);

        $this->json('PUT', "/api/v1/articles/{$article->uuid}", [
            'hashtags' => ['#new1', '#new2']
        ])->assertStatus(200);

        $hashtags = $this->getHashtagContents($article);
        sort($hashtags);

        $this->assertSame(['#new1', '#new2'], $hashtags);
    }

    public function test_update_hashtags_clear_allows_empty_array(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user);

        Passport::actingAs($user, ['*'], 'api');

        $this->json('PUT', "/api/v1/articles/{$article->uuid}", [
            'hashtags' => ['#one', '#two']
        ])->assertStatus(200);

        $this->json('PUT', "/api/v1/articles/{$article->uuid}", [
            'hashtags' => []
        ])->assertStatus(200);

        $this->assertSame([], $this->getHashtagContents($article));
    }

    public function test_update_accepts_legacy_tags_alias(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user);

        Passport::actingAs($user, ['*'], 'api');

        $this->json('PUT', "/api/v1/articles/{$article->uuid}", [
            'tags' => ['#legacy']
        ])->assertStatus(200);

        $this->assertSame(['#legacy'], $this->getHashtagContents($article));
    }

    public function test_update_content_jp_dispatches_job(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user);

        Passport::actingAs($user, ['*'], 'api');
        Bus::fake();

        $this->json('PUT', "/api/v1/articles/{$article->uuid}", [
            'content_jp' => 'Updated Japanese content text.'
        ])->assertStatus(200);

        Bus::assertDispatched(ProcessArticleKanjisJob::class);
    }
}
