<?php

namespace Tests\Feature\Articles;

use App\Application\Articles\Jobs\ProcessArticleKanjisJob;
use App\Application\Articles\Jobs\ProcessArticleWordsJob;
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
use ReflectionClass;
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

    public function test_update_content_jp_dispatches_job(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user);

        Passport::actingAs($user, ['*'], 'api');
        Bus::fake();

        $this->json('PUT', "/api/v1/articles/{$article->uuid}", [
            'content_jp' => 'Updated Japanese content text.',
        ])->assertStatus(200);

        Bus::assertDispatched(ProcessArticleKanjisJob::class);
    }

    public function test_update_title_jp_dispatches_word_job_only(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user, [
            'title_jp' => '古いタイトル',
            'content_jp' => '古い本文です。日本語の本文です。',
        ]);

        Passport::actingAs($user, ['*'], 'api');
        Bus::fake();

        $newTitle = '新しいタイトル';

        $this->json('PUT', "/api/v1/articles/{$article->uuid}", [
            'title_jp' => $newTitle,
        ])->assertStatus(200);

        Bus::assertNotDispatched(ProcessArticleKanjisJob::class);
        Bus::assertDispatched(
            ProcessArticleWordsJob::class,
            fn (ProcessArticleWordsJob $job): bool => $this->readJobProperty($job, 'articleUuid') === $article->uuid
                && $this->readJobProperty($job, 'articleText') === $newTitle.$article->content_jp
        );
    }

    public function test_update_content_jp_dispatches_kanji_and_word_jobs(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user, [
            'title_jp' => '学校の話',
            'content_jp' => '古い本文です。日本語の本文です。',
        ]);

        Passport::actingAs($user, ['*'], 'api');
        Bus::fake();

        $newContent = '更新された日本語本文です。学校で勉強します。';

        $this->json('PUT', "/api/v1/articles/{$article->uuid}", [
            'content_jp' => $newContent,
        ])->assertStatus(200);

        Bus::assertDispatched(ProcessArticleKanjisJob::class);
        Bus::assertDispatched(
            ProcessArticleWordsJob::class,
            fn (ProcessArticleWordsJob $job): bool => $this->readJobProperty($job, 'articleUuid') === $article->uuid
                && $this->readJobProperty($job, 'articleText') === $article->title_jp.$newContent
        );
    }

    public function test_update_metadata_only_does_not_dispatch_word_or_kanji_jobs(): void
    {
        $user = $this->createUser();
        $article = $this->createArticle($user);

        Passport::actingAs($user, ['*'], 'api');
        Bus::fake();

        $this->json('PUT', "/api/v1/articles/{$article->uuid}", [
            'source_link' => 'https://example.com/updated-source',
        ])->assertStatus(200);

        Bus::assertNotDispatched(ProcessArticleKanjisJob::class);
        Bus::assertNotDispatched(ProcessArticleWordsJob::class);
    }

    private function readJobProperty(object $job, string $property): mixed
    {
        $reflection = new ReflectionClass($job);
        $jobProperty = $reflection->getProperty($property);
        $jobProperty->setAccessible(true);

        return $jobProperty->getValue($job);
    }
}
