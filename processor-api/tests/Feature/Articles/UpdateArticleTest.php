<?php

namespace Tests\Feature\Articles;

use App\Application\Articles\Jobs\ProcessArticleContentJob;
use App\Domain\Processing\Enums\ProcessingStatus;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Models\HashtagEntity;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

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
            'title_jp' => 'Updated title',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('title_jp', 'Updated title');
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
        $otherUser = $this->createUser(['email' => Str::uuid().'@example.com']);
        $article = $this->createArticle($owner);

        Passport::actingAs($otherUser, ['*'], 'api');

        $response = $this->json('PUT', "/api/v1/articles/{$article->uuid}", [
            'title_jp' => 'Attempted update',
        ]);

        $response->assertStatus(403);
    }

    public function test_update_unknown_uuid_returns_not_found(): void
    {
        $user = $this->createUser();

        Passport::actingAs($user, ['*'], 'api');

        $response = $this->json('PUT', '/api/v1/articles/'.(string) Str::uuid(), [
            'title_jp' => 'Updated title',
        ]);

        $response->assertStatus(404);
    }

    public function test_update_hashtags_replaces_existing(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user);

        Passport::actingAs($user, ['*'], 'api');

        $this->json('PUT', "/api/v1/articles/{$article->uuid}", [
            'hashtags' => ['#old'],
        ])->assertStatus(200);

        $response = $this->json('PUT', "/api/v1/articles/{$article->uuid}", [
            'hashtags' => ['#new1', '#new2'],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('hashtags.0.content', '#new1')
            ->assertJsonPath('hashtags.1.content', '#new2');

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
            'hashtags' => ['#one', '#two'],
        ])->assertStatus(200);

        $this->json('PUT', "/api/v1/articles/{$article->uuid}", [
            'hashtags' => [],
        ])->assertStatus(200);

        $this->assertSame([], $this->getHashtagContents($article));
    }

    public function test_update_accepts_legacy_tags_alias(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user);

        Passport::actingAs($user, ['*'], 'api');

        $this->json('PUT', "/api/v1/articles/{$article->uuid}", [
            'tags' => ['#legacy'],
        ])->assertStatus(200);

        $this->assertSame(['#legacy'], $this->getHashtagContents($article));
    }

    public function test_update_content_jp_dispatches_one_job_with_the_bumped_version(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user);

        Passport::actingAs($user, ['*'], 'api');
        Bus::fake();

        $this->json('PUT', "/api/v1/articles/{$article->uuid}", [
            'content_jp' => 'Updated Japanese content text.',
        ])
            ->assertStatus(200)
            ->assertJsonPath('processing_status.status', ProcessingStatus::PENDING->value)
            ->assertJsonPath('processing_status.type', 'article_content_processing');

        $this->assertDispatchedOnceFor($article->uuid, version: 2);
        $this->assertDatabaseHas('articles', ['uuid' => $article->uuid, 'content_version' => 2]);
        $this->assertDatabaseHas('processing_states', [
            'entity_id' => $article->uuid,
            'task_type' => 'article_content_processing',
            'status' => ProcessingStatus::PENDING->value,
            'content_version' => 2,
        ]);
    }

    public function test_update_title_jp_alone_dispatches_the_job(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user, [
            'title_jp' => '古いタイトル',
            'content_jp' => '古い本文です。日本語の本文です。',
        ]);

        Passport::actingAs($user, ['*'], 'api');
        Bus::fake();

        $this->json('PUT', "/api/v1/articles/{$article->uuid}", [
            'title_jp' => '新しいタイトル',
        ])->assertStatus(200);

        $this->assertDispatchedOnceFor($article->uuid, version: 2);
    }

    public function test_each_content_edit_bumps_the_version_again(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user);

        Passport::actingAs($user, ['*'], 'api');
        Bus::fake();

        $this->json('PUT', "/api/v1/articles/{$article->uuid}", ['content_jp' => '一回目の更新です。日本語の本文です。'])->assertStatus(200);
        $this->json('PUT', "/api/v1/articles/{$article->uuid}", ['content_jp' => '二回目の更新です。日本語の本文です。'])->assertStatus(200);

        Bus::assertDispatchedTimes(ProcessArticleContentJob::class, 2);
        Bus::assertDispatched(ProcessArticleContentJob::class, fn (ProcessArticleContentJob $job): bool => $job->contentVersion === 2);
        Bus::assertDispatched(ProcessArticleContentJob::class, fn (ProcessArticleContentJob $job): bool => $job->contentVersion === 3);
        $this->assertDatabaseHas('articles', ['uuid' => $article->uuid, 'content_version' => 3]);
    }

    public function test_unchanged_japanese_fields_do_not_dispatch_or_bump(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user);

        Passport::actingAs($user, ['*'], 'api');
        Bus::fake();

        $this->json('PUT', "/api/v1/articles/{$article->uuid}", [
            'title_jp' => $article->title_jp,
            'content_jp' => $article->content_jp,
            'source_link' => 'https://example.com/updated-source',
        ])->assertStatus(200);

        Bus::assertNotDispatched(ProcessArticleContentJob::class);
        $this->assertDatabaseHas('articles', ['uuid' => $article->uuid, 'content_version' => 1]);
        $this->assertDatabaseMissing('processing_states', ['entity_id' => $article->uuid]);
    }

    public function test_update_metadata_only_does_not_dispatch(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user);

        Passport::actingAs($user, ['*'], 'api');
        Bus::fake();

        $this->json('PUT', "/api/v1/articles/{$article->uuid}", [
            'source_link' => 'https://example.com/updated-source',
        ])->assertStatus(200);

        Bus::assertNotDispatched(ProcessArticleContentJob::class);
    }

    private function assertDispatchedOnceFor(string $articleUuid, int $version): void
    {
        Bus::assertDispatchedTimes(ProcessArticleContentJob::class, 1);
        Bus::assertDispatched(
            ProcessArticleContentJob::class,
            fn (ProcessArticleContentJob $job): bool => $job->articleUuid === $articleUuid && $job->contentVersion === $version,
        );
    }
}
