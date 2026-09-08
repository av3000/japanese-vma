<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Posts;

use App\Domain\Community\Posts\Enums\PostTopic;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Infrastructure\Persistence\Models\Post as PersistencePost;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

class PostReadV1Test extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
    }

    public function test_list_returns_the_public_contract_shape(): void
    {
        $post = $this->createPost(['title' => 'Community question', 'type' => (string) PostTopic::FAQ->value]);
        $this->attachHashtag($post, '#grammar');

        $response = $this->getJson('/api/v1/posts');

        $response->assertOk()
            ->assertJsonStructure([
                'items' => [
                    [
                        'id', 'uuid', 'entity_type_uuid', 'title', 'topic', 'topic_label', 'locked',
                        'author' => ['id', 'uuid', 'name'],
                        'hashtags' => [['id', 'content']],
                        'engagement' => ['stats' => ['likes_count', 'views_count', 'downloads_count', 'comments_count']],
                        'created_at', 'updated_at',
                    ],
                ],
                'pagination' => ['page', 'per_page', 'total', 'last_page', 'has_more'],
            ]);

        $item = $response->json('items.0');

        self::assertSame($post->id, $item['id']);
        self::assertSame($post->uuid, $item['uuid']);
        self::assertSame(ObjectTemplateType::POST->value, $item['entity_type_uuid']);
        self::assertSame(3, $item['topic']);
        self::assertSame('FAQ', $item['topic_label']);
        self::assertFalse($item['locked']);
        self::assertArrayNotHasKey('content', $item, 'list items must not carry full content');
        self::assertArrayNotHasKey('comments', $item);
        self::assertSame(['page' => 1, 'per_page' => 5, 'total' => 1, 'last_page' => 1, 'has_more' => false], $response->json('pagination'));
    }

    public function test_topic_label_does_not_inherit_the_legacy_defect(): void
    {
        $this->createPost(['title' => 'Feedback post', 'type' => (string) PostTopic::FEEDBACK->value]);
        $this->createPost(['title' => 'Announcement post', 'type' => (string) PostTopic::ANNOUNCEMENT->value]);

        $labels = collect($this->getJson('/api/v1/posts')->json('items'))
            ->pluck('topic_label', 'topic')
            ->all();

        self::assertSame('Feedback', $labels[6]);
        self::assertSame('Announcement', $labels[7]);
    }

    public function test_filters_and_sorting_narrow_the_page(): void
    {
        $faq = $this->createPost(['title' => 'Grammar FAQ', 'type' => (string) PostTopic::FAQ->value, 'created_at' => '2026-01-01 10:00:00']);
        $this->createPost(['title' => 'Bug report', 'type' => (string) PostTopic::BUG->value, 'created_at' => '2026-02-01 10:00:00']);
        $this->attachHashtag($faq, '#grammar');

        self::assertSame(['Grammar FAQ'], $this->titles($this->getJson('/api/v1/posts?keyword=grammar')));
        self::assertSame(['Grammar FAQ'], $this->titles($this->getJson('/api/v1/posts?hashtag=grammar')));
        self::assertSame(['Grammar FAQ'], $this->titles($this->getJson('/api/v1/posts?topic=3')));
        self::assertSame(['Bug report', 'Grammar FAQ'], $this->titles($this->getJson('/api/v1/posts?sort=newest')));
        self::assertSame(['Bug report', 'Grammar FAQ'], $this->titles($this->getJson('/api/v1/posts?sort=popular')));
    }

    public function test_an_unmatched_filter_is_an_empty_success(): void
    {
        $this->createPost();

        $response = $this->getJson('/api/v1/posts?keyword=nothing-matches');

        $response->assertOk();
        self::assertSame([], $response->json('items'));
        self::assertSame(0, $response->json('pagination.total'));
    }

    public function test_invalid_query_parameters_are_rejected(): void
    {
        $this->getJson('/api/v1/posts?per_page=51')->assertStatus(422);
        $this->getJson('/api/v1/posts?page=0')->assertStatus(422);
        $this->getJson('/api/v1/posts?topic=8')->assertStatus(422);
        $this->getJson('/api/v1/posts?sort=oldest')->assertStatus(422);
    }

    public function test_locked_posts_stay_publicly_visible(): void
    {
        $post = $this->createPost(['title' => 'Locked post', 'locked' => true]);

        self::assertTrue($this->getJson('/api/v1/posts')->json('items.0.locked'));
        self::assertTrue($this->getJson("/api/v1/posts/{$post->uuid}")->json('locked'));
    }

    public function test_detail_by_uuid_returns_content_and_all_hashtags(): void
    {
        $post = $this->createPost(['title' => 'Detail post', 'content' => 'Full post content']);
        $this->attachHashtag($post, '#one');
        $this->attachHashtag($post, '#two');
        $this->attachHashtag($post, '#three');
        $this->attachHashtag($post, '#four');

        $response = $this->getJson("/api/v1/posts/{$post->uuid}");

        $response->assertOk();
        self::assertSame('Full post content', $response->json('content'));
        self::assertSame(['#one', '#two', '#three', '#four'], array_column($response->json('hashtags'), 'content'));
        self::assertNull($response->json('comments'));
    }

    public function test_a_numeric_identifier_resolves_and_returns_the_canonical_uuid(): void
    {
        $post = $this->createPost();

        $response = $this->getJson("/api/v1/posts/{$post->id}");

        $response->assertOk();
        self::assertSame($post->uuid, $response->json('uuid'));
    }

    public function test_unresolvable_identifiers_return_404(): void
    {
        $this->getJson('/api/v1/posts/922f91b4-cbe8-4ca0-8bf8-50de48f5d086')->assertStatus(404);
        $this->getJson('/api/v1/posts/999999')->assertStatus(404);
    }

    public function test_a_malformed_identifier_returns_400(): void
    {
        $this->getJson('/api/v1/posts/not-a-uuid')
            ->assertStatus(400)
            ->assertJsonPath('title', 'Identifier must be a valid UUID or numeric post ID.');
    }

    public function test_authenticated_detail_requests_record_exactly_one_view(): void
    {
        $post = $this->createPost();
        $reader = User::factory()->create();

        Passport::actingAs($reader);

        $this->getJson("/api/v1/posts/{$post->uuid}")->assertOk();
        $this->getJson("/api/v1/posts/{$post->uuid}")->assertOk();

        self::assertSame(1, DB::table('views')
            ->where('template_id', ObjectTemplateType::POST->getLegacyId())
            ->where('real_object_id', $post->id)
            ->count());
    }

    public function test_the_viewers_own_view_is_reflected_in_the_response_counts(): void
    {
        $post = $this->createPost();

        Passport::actingAs(User::factory()->create());

        self::assertSame(1, $this->getJson("/api/v1/posts/{$post->uuid}")->json('engagement.stats.views_count'));
    }

    public function test_anonymous_detail_requests_record_no_view(): void
    {
        $post = $this->createPost();

        $this->getJson("/api/v1/posts/{$post->uuid}")->assertOk();

        self::assertSame(0, DB::table('views')
            ->where('template_id', ObjectTemplateType::POST->getLegacyId())
            ->where('real_object_id', $post->id)
            ->count());
    }

    public function test_legacy_post_routes_still_respond(): void
    {
        $this->createPost();

        $this->getJson('/api/posts')->assertOk()->assertJsonPath('success', true);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createPost(array $overrides = []): PersistencePost
    {
        return PersistencePost::factory()->create($overrides);
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
     * @return array<int, string>
     */
    private function titles(\Illuminate\Testing\TestResponse $response): array
    {
        return array_column($response->json('items'), 'title');
    }
}
