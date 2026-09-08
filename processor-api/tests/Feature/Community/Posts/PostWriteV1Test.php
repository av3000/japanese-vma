<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Posts;

use App\Application\Engagement\Services\HashtagServiceInterface;
use App\Domain\Community\Posts\Enums\PostTopic;
use App\Domain\Engagement\Errors\HashtagErrors;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\Post as PersistencePost;
use App\Infrastructure\Persistence\Models\User;
use App\Shared\Results\Result;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

class PostWriteV1Test extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
    }

    // ============================================
    // Create
    // ============================================

    public function test_owner_creates_a_post_and_receives_the_canonical_detail_shape(): void
    {
        $author = $this->createUser();
        Passport::actingAs($author, ['*'], 'api');

        $response = $this->postJson('/api/v1/posts', [
            'title' => 'How do I read this kanji?',
            'content' => 'I keep seeing this compound and cannot find it in any dictionary.',
            'topic' => PostTopic::FAQ->value,
            'tags' => ['#kanji', '#help'],
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'id', 'uuid', 'entity_type_uuid', 'title', 'topic', 'topic_label', 'locked', 'content',
                'author' => ['id', 'uuid', 'name'],
                'hashtags' => [['id', 'content']],
                'engagement' => ['stats' => ['likes_count', 'views_count', 'downloads_count', 'comments_count']],
                'created_at', 'updated_at',
            ]);

        self::assertTrue(Str::isUuid($response->json('uuid')));
        self::assertSame(ObjectTemplateType::POST->value, $response->json('entity_type_uuid'));
        self::assertSame(PostTopic::FAQ->value, $response->json('topic'));
        self::assertSame('FAQ', $response->json('topic_label'));
        self::assertFalse($response->json('locked'));
        self::assertSame($author->id, $response->json('author.id'));
        self::assertEqualsCanonicalizing(
            ['#kanji', '#help'],
            array_column($response->json('hashtags'), 'content'),
        );

        $this->assertDatabaseHas('posts', [
            'uuid' => $response->json('uuid'),
            'user_id' => $author->id,
            'type' => (string) PostTopic::FAQ->value,
            'locked' => false,
        ]);
    }

    public function test_create_records_the_authors_initial_view(): void
    {
        $author = $this->createUser();
        Passport::actingAs($author, ['*'], 'api');

        // Legacy PostController::store() called incrementView() straight after
        // save, so a brand new Post reads as one view rather than zero.
        $response = $this->postJson('/api/v1/posts', $this->validPayload());

        $response->assertCreated();
        self::assertSame(1, $response->json('engagement.stats.views_count'));

        $this->assertDatabaseHas('views', [
            'real_object_id' => $response->json('id'),
            'template_id' => ObjectTemplateType::POST->getLegacyId(),
            'user_id' => $author->id,
        ]);
    }

    public function test_guest_cannot_create_a_post(): void
    {
        $this->postJson('/api/v1/posts', $this->validPayload())->assertUnauthorized();
    }

    public function test_create_rejects_invalid_payloads(): void
    {
        Passport::actingAs($this->createUser(), ['*'], 'api');

        $cases = [
            'missing everything' => [[], ['title', 'content', 'topic']],
            'title too short' => [$this->validPayload(['title' => 'a']), ['title']],
            'content too short' => [$this->validPayload(['content' => 'four']), ['content']],
            'topic outside the canonical range' => [$this->validPayload(['topic' => 8]), ['topic']],
            'topic not numeric' => [$this->validPayload(['topic' => 'faq']), ['topic']],
            'too many tags' => [
                $this->validPayload(['tags' => array_map(static fn (int $i): string => "#tag{$i}", range(1, 11))]),
                ['tags'],
            ],
        ];

        foreach ($cases as $label => [$payload, $expectedErrors]) {
            $this->postJson('/api/v1/posts', $payload)
                ->assertUnprocessable()
                ->assertJsonValidationErrors($expectedErrors, responseKey: 'errors');

            self::assertSame(0, PersistencePost::query()->count(), "{$label} must not persist a Post");
        }
    }

    public function test_create_accepts_the_legacy_type_field_name(): void
    {
        Passport::actingAs($this->createUser(), ['*'], 'api');

        $payload = $this->validPayload();
        $payload['type'] = $payload['topic'];
        unset($payload['topic']);

        $this->postJson('/api/v1/posts', $payload)
            ->assertCreated()
            ->assertJsonPath('topic', PostTopic::OFF_TOPIC->value);
    }

    public function test_create_rolls_back_the_post_when_hashtag_writes_fail(): void
    {
        Passport::actingAs($this->createUser(), ['*'], 'api');
        $this->failHashtagSync();

        $this->postJson('/api/v1/posts', $this->validPayload(['tags' => ['#spam']]))
            ->assertUnprocessable();

        self::assertSame(0, PersistencePost::query()->count(), 'the Post must not survive a failed tag write');
    }

    // ============================================
    // Update
    // ============================================

    public function test_owner_updates_title_content_topic_and_tags(): void
    {
        $author = $this->createUser();
        $post = $this->createPost(['user_id' => $author->id, 'type' => (string) PostTopic::OFF_TOPIC->value]);
        $this->attachHashtag($post, '#stale');
        Passport::actingAs($author, ['*'], 'api');

        $response = $this->putJson("/api/v1/posts/{$post->uuid}", [
            'title' => 'Rewritten title',
            'content' => 'Rewritten body that is comfortably long enough.',
            'topic' => PostTopic::TECHNICAL->value,
            'tags' => ['#fresh'],
        ]);

        $response->assertOk()
            ->assertJsonPath('uuid', $post->uuid)
            ->assertJsonPath('title', 'Rewritten title')
            ->assertJsonPath('topic', PostTopic::TECHNICAL->value)
            ->assertJsonPath('topic_label', 'Technical');

        self::assertSame(['#fresh'], array_column($response->json('hashtags'), 'content'));
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Rewritten title']);
    }

    public function test_update_leaves_unmentioned_fields_untouched(): void
    {
        $author = $this->createUser();
        $post = $this->createPost(['user_id' => $author->id, 'title' => 'Original title']);
        $this->attachHashtag($post, '#kept');
        Passport::actingAs($author, ['*'], 'api');

        $response = $this->putJson("/api/v1/posts/{$post->uuid}", [
            'content' => 'Only the body changed in this request.',
        ]);

        $response->assertOk()
            ->assertJsonPath('title', 'Original title')
            ->assertJsonPath('content', 'Only the body changed in this request.');

        self::assertSame(['#kept'], array_column($response->json('hashtags'), 'content'));
    }

    public function test_update_with_an_empty_tag_array_clears_every_tag(): void
    {
        $author = $this->createUser();
        $post = $this->createPost(['user_id' => $author->id]);
        $this->attachHashtag($post, '#doomed');
        Passport::actingAs($author, ['*'], 'api');

        $this->putJson("/api/v1/posts/{$post->uuid}", ['tags' => []])
            ->assertOk()
            ->assertJsonPath('hashtags', []);
    }

    public function test_update_rejects_an_empty_body(): void
    {
        $author = $this->createUser();
        $post = $this->createPost(['user_id' => $author->id]);
        Passport::actingAs($author, ['*'], 'api');

        $this->putJson("/api/v1/posts/{$post->uuid}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['post'], responseKey: 'errors');
    }

    public function test_update_rejects_an_invalid_topic(): void
    {
        $author = $this->createUser();
        $post = $this->createPost(['user_id' => $author->id]);
        Passport::actingAs($author, ['*'], 'api');

        $this->putJson("/api/v1/posts/{$post->uuid}", ['topic' => 99])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['topic'], responseKey: 'errors');
    }

    public function test_guest_cannot_update_a_post(): void
    {
        $post = $this->createPost();

        $this->putJson("/api/v1/posts/{$post->uuid}", ['title' => 'Hijacked'])
            ->assertUnauthorized();
    }

    public function test_non_owner_cannot_update_a_post(): void
    {
        $post = $this->createPost();
        Passport::actingAs($this->createUser(), ['*'], 'api');

        $this->putJson("/api/v1/posts/{$post->uuid}", ['title' => 'Hijacked'])
            ->assertForbidden();

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => $post->title]);
    }

    public function test_admin_cannot_update_another_users_post(): void
    {
        // Deliberate: an admin moderates by locking or deleting, not by editing
        // another author's words. Legacy update was owner-only too.
        $post = $this->createPost();
        Passport::actingAs($this->createAdmin(), ['*'], 'api');

        $this->putJson("/api/v1/posts/{$post->uuid}", ['title' => 'Moderated'])
            ->assertForbidden();
    }

    public function test_update_of_an_unknown_uuid_is_not_found(): void
    {
        Passport::actingAs($this->createUser(), ['*'], 'api');

        $this->putJson('/api/v1/posts/'.Str::uuid(), ['title' => 'Nowhere'])
            ->assertNotFound();
    }

    public function test_update_rolls_back_when_hashtag_writes_fail(): void
    {
        $author = $this->createUser();
        $post = $this->createPost(['user_id' => $author->id, 'title' => 'Original title']);
        Passport::actingAs($author, ['*'], 'api');
        $this->failHashtagSync();

        $this->putJson("/api/v1/posts/{$post->uuid}", [
            'title' => 'Rewritten title',
            'tags' => ['#spam'],
        ])->assertUnprocessable();

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Original title']);
    }

    // ============================================
    // Delete
    // ============================================

    public function test_owner_deletes_a_post_and_its_dependent_rows(): void
    {
        $author = $this->createUser();
        $post = $this->createPost(['user_id' => $author->id]);
        $this->attachHashtag($post, '#doomed');
        $this->attachEngagement($post);
        Passport::actingAs($author, ['*'], 'api');

        $this->deleteJson("/api/v1/posts/{$post->uuid}")->assertNoContent();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);

        $templateId = ObjectTemplateType::POST->getLegacyId();
        foreach (['views', 'likes', 'comments'] as $table) {
            self::assertSame(
                0,
                DB::table($table)->where('real_object_id', $post->id)->where('template_id', $templateId)->count(),
                "{$table} rows must not outlive the Post",
            );
        }

        // hashtag_entity soft-deletes, so the row survives with deleted_at set.
        // What matters is that no live link remains - the same `whereNull` the
        // read repository filters on.
        self::assertSame(
            0,
            DB::table('hashtag_entity')
                ->where('entity_id', $post->id)
                ->whereNull('deleted_at')
                ->count(),
        );
    }

    public function test_admin_deletes_another_users_post(): void
    {
        $post = $this->createPost();
        Passport::actingAs($this->createAdmin(), ['*'], 'api');

        $this->deleteJson("/api/v1/posts/{$post->uuid}")->assertNoContent();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_guest_cannot_delete_a_post(): void
    {
        $post = $this->createPost();

        $this->deleteJson("/api/v1/posts/{$post->uuid}")->assertUnauthorized();
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_non_owner_cannot_delete_a_post(): void
    {
        $post = $this->createPost();
        Passport::actingAs($this->createUser(), ['*'], 'api');

        $this->deleteJson("/api/v1/posts/{$post->uuid}")->assertForbidden();
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_delete_of_an_unknown_uuid_is_not_found(): void
    {
        Passport::actingAs($this->createUser(), ['*'], 'api');

        $this->deleteJson('/api/v1/posts/'.Str::uuid())->assertNotFound();
    }

    // ============================================
    // Lock
    // ============================================

    public function test_admin_locks_and_unlocks_a_post(): void
    {
        $post = $this->createPost();
        Passport::actingAs($this->createAdmin(), ['*'], 'api');

        $this->putJson("/api/v1/posts/{$post->uuid}/lock", ['locked' => true])
            ->assertOk()
            ->assertExactJson(['uuid' => $post->uuid, 'locked' => true]);

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'locked' => true]);

        $this->putJson("/api/v1/posts/{$post->uuid}/lock", ['locked' => false])
            ->assertOk()
            ->assertExactJson(['uuid' => $post->uuid, 'locked' => false]);

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'locked' => false]);
    }

    public function test_locking_is_idempotent(): void
    {
        $post = $this->createPost();
        Passport::actingAs($this->createAdmin(), ['*'], 'api');

        // The legacy toggle flipped state back on a retry; the same request
        // twice must land on the same state here.
        foreach (range(1, 2) as $ignored) {
            $this->putJson("/api/v1/posts/{$post->uuid}/lock", ['locked' => true])
                ->assertOk()
                ->assertJsonPath('locked', true);
        }

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'locked' => true]);
    }

    public function test_lock_requires_an_explicit_boolean_state(): void
    {
        $post = $this->createPost();
        Passport::actingAs($this->createAdmin(), ['*'], 'api');

        foreach ([[], ['locked' => 'maybe']] as $payload) {
            $this->putJson("/api/v1/posts/{$post->uuid}/lock", $payload)
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['locked'], responseKey: 'errors');
        }
    }

    public function test_guest_cannot_lock_a_post(): void
    {
        $post = $this->createPost();

        $this->putJson("/api/v1/posts/{$post->uuid}/lock", ['locked' => true])
            ->assertUnauthorized();
    }

    public function test_post_owner_without_admin_cannot_lock_their_own_post(): void
    {
        $author = $this->createUser();
        $post = $this->createPost(['user_id' => $author->id]);
        Passport::actingAs($author, ['*'], 'api');

        $this->putJson("/api/v1/posts/{$post->uuid}/lock", ['locked' => true])
            ->assertForbidden();

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'locked' => false]);
    }

    public function test_lock_of_an_unknown_uuid_is_not_found_for_an_admin(): void
    {
        Passport::actingAs($this->createAdmin(), ['*'], 'api');

        $this->putJson('/api/v1/posts/'.Str::uuid().'/lock', ['locked' => true])
            ->assertNotFound();
    }

    // ============================================
    // Compatibility
    // ============================================

    public function test_legacy_post_routes_are_still_registered(): void
    {
        // The legacy transport stays until POST-WRITE-FE-01 migrates the client,
        // so this slice must not remove or shadow any of it.
        //
        // Only registration is asserted for the write routes. Legacy creation is
        // already broken on develop and not by this slice: App\Http\Models\Post
        // never generates a uuid, and 2025_10_05_201410_add_required_uuid_to_tables
        // made posts.uuid NOT NULL, so PostController::store() has been throwing a
        // not-null violation since that migration. Asserting a 200 here would be
        // asserting a bug is fixed that this slice does not touch.
        $routes = collect(Route::getRoutes()->getRoutes())
            ->map(static fn ($route): string => $route->methods()[0].' '.$route->uri())
            ->all();

        foreach ([
            'POST api/post',
            'PUT api/post/{id}',
            'DELETE api/post/{id}',
            // The two duplicate lock paths differ only in the case of one
            // letter. v1 replaces both with a single explicit-state route.
            'POST api/post/{id}/toggleLock',
            'POST api/post/{id}/togglelock',
            'GET api/posts',
            'GET api/post/{id}',
        ] as $expected) {
            self::assertContains($expected, $routes, "legacy route {$expected} must survive this slice");
        }
    }

    public function test_legacy_post_read_still_responds(): void
    {
        $post = $this->createPost();

        $this->getJson("/api/post/{$post->id}")
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    // ============================================
    // Helpers
    // ============================================

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'A perfectly ordinary post',
            'content' => 'Body text that clears the minimum length requirement.',
            'topic' => PostTopic::OFF_TOPIC->value,
        ], $overrides);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createPost(array $overrides = []): PersistencePost
    {
        return PersistencePost::factory()->create($overrides);
    }

    private function createUser(): User
    {
        return User::create([
            'name' => 'Post Test User',
            'email' => Str::uuid().'@example.com',
            'password' => bcrypt('password'),
            'uuid' => (string) Str::uuid(),
        ]);
    }

    private function createAdmin(): User
    {
        $admin = $this->createUser();
        $admin->assignRole(UserRole::ADMIN->value);

        return $admin;
    }

    private function attachHashtag(PersistencePost $post, string $content): void
    {
        $hashtagId = DB::table('uniquehashtags')->insertGetId([
            'content' => $content,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('hashtag_entity')->insert([
            'entity_id' => $post->id,
            'entity_type_id' => ObjectTemplateType::POST->getLegacyId(),
            'hashtag_id' => $hashtagId,
            'user_id' => $post->user_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * A view, a like and a comment - the rows legacy removeImpressions() cleared.
     */
    private function attachEngagement(PersistencePost $post): void
    {
        $templateId = ObjectTemplateType::POST->getLegacyId();

        DB::table('views')->insert([
            'user_id' => $post->user_id,
            'user_ip' => '127.0.0.1',
            'template_id' => $templateId,
            'real_object_id' => $post->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('likes')->insert([
            'user_id' => $post->user_id,
            'template_id' => $templateId,
            'real_object_id' => $post->id,
            'value' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('comments')->insert([
            'uuid' => (string) Str::uuid(),
            'user_id' => $post->user_id,
            'template_id' => $templateId,
            'real_object_id' => $post->id,
            'content' => 'A comment that should not outlive its post.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Forces the hashtag port to reject, so the surrounding transaction is the
     * only thing standing between a failed tag write and a half-written Post.
     */
    private function failHashtagSync(): void
    {
        $hashtagService = $this->createMock(HashtagServiceInterface::class);
        $hashtagService->method('syncTagsForEntity')
            ->willReturn(Result::failure(HashtagErrors::invalidTag('#spam')));
        $hashtagService->method('getHashtags')->willReturn([]);
        $hashtagService->method('getBatchHashtags')->willReturn([]);

        $this->app->instance(HashtagServiceInterface::class, $hashtagService);
    }
}
