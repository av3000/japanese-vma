<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Posts;

use App\Domain\Community\Posts\Enums\PostSort;
use App\Domain\Community\Posts\Enums\PostTopic;
use App\Domain\Community\Posts\Models\Post as DomainPost;
use App\Domain\Community\Posts\Queries\PostQueryCriteria;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\Post as PersistencePost;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Repositories\PostMapper;
use App\Infrastructure\Persistence\Repositories\PostRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

class PostRepositoryTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    private PostRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();

        $this->repository = new PostRepository(new PostMapper);
    }

    public function test_keyword_matches_title_or_content_case_insensitively(): void
    {
        $this->createPost(['title' => 'Grammar Question', 'content' => 'unrelated body']);
        $this->createPost(['title' => 'unrelated title', 'content' => 'A GRAMMAR body']);
        $this->createPost(['title' => 'Kanji question', 'content' => 'nothing here']);

        $titles = $this->titles($this->repository->find(
            PostQueryCriteria::forListing(keyword: 'grammar'),
        )->items);

        self::assertCount(2, $titles);
        self::assertContains('Grammar Question', $titles);
        self::assertContains('unrelated title', $titles);
    }

    public function test_hashtag_filter_accepts_the_tag_with_or_without_a_leading_hash(): void
    {
        $tagged = $this->createPost(['title' => 'Tagged post']);
        $this->createPost(['title' => 'Untagged post']);
        $this->attachHashtag($tagged, '#grammar');

        foreach (['#grammar', 'grammar'] as $input) {
            $result = $this->repository->find(PostQueryCriteria::forListing(hashtag: $input));

            self::assertSame(['Tagged post'], $this->titles($result->items), "input: {$input}");
        }
    }

    public function test_hashtag_filter_ignores_soft_deleted_links(): void
    {
        $tagged = $this->createPost(['title' => 'Tagged post']);
        $this->attachHashtag($tagged, '#grammar', softDeleted: true);

        $result = $this->repository->find(PostQueryCriteria::forListing(hashtag: '#grammar'));

        self::assertSame([], $this->titles($result->items));
    }

    public function test_topic_filter_matches_the_persisted_string_code(): void
    {
        $this->createPost(['title' => 'FAQ post', 'type' => (string) PostTopic::FAQ->value]);
        $this->createPost(['title' => 'Bug post', 'type' => (string) PostTopic::BUG->value]);

        $result = $this->repository->find(PostQueryCriteria::forListing(topic: PostTopic::FAQ));

        self::assertSame(['FAQ post'], $this->titles($result->items));
    }

    public function test_newest_sort_is_deterministic(): void
    {
        $this->createPost(['title' => 'Oldest', 'created_at' => '2026-01-01 10:00:00']);
        $this->createPost(['title' => 'Newest', 'created_at' => '2026-03-01 10:00:00']);
        $this->createPost(['title' => 'Middle', 'created_at' => '2026-02-01 10:00:00']);

        $result = $this->repository->find(PostQueryCriteria::forListing(sort: PostSort::NEWEST));

        self::assertSame(['Newest', 'Middle', 'Oldest'], $this->titles($result->items));
    }

    public function test_popular_sort_orders_by_view_count_and_keeps_posts_without_views(): void
    {
        $watched = $this->createPost(['title' => 'Watched', 'created_at' => '2026-01-01 10:00:00']);
        $ignored = $this->createPost(['title' => 'Ignored', 'created_at' => '2026-02-01 10:00:00']);
        $seenOnce = $this->createPost(['title' => 'Seen once', 'created_at' => '2026-03-01 10:00:00']);

        $this->recordViews($watched, 3);
        $this->recordViews($seenOnce, 1);

        $result = $this->repository->find(PostQueryCriteria::forListing(sort: PostSort::POPULAR));

        self::assertSame(['Watched', 'Seen once', 'Ignored'], $this->titles($result->items));
        self::assertSame(3, $result->pagination['total'], 'zero-view posts must not be dropped');
        self::assertNotNull($ignored->id);
    }

    public function test_pagination_metadata_reflects_the_requested_page(): void
    {
        foreach (range(1, 7) as $index) {
            $this->createPost([
                'title' => "Post {$index}",
                'created_at' => sprintf('2026-01-%02d 10:00:00', $index),
            ]);
        }

        $result = $this->repository->find(PostQueryCriteria::forListing(page: 2, perPage: 5));

        self::assertSame(['Post 2', 'Post 1'], $this->titles($result->items));
        self::assertSame(
            ['page' => 2, 'per_page' => 5, 'total' => 7, 'last_page' => 2, 'has_more' => false],
            $result->pagination,
        );
    }

    public function test_locked_posts_stay_visible_in_results(): void
    {
        $this->createPost(['title' => 'Locked post', 'locked' => true]);

        $result = $this->repository->find(PostQueryCriteria::forListing());

        self::assertSame(['Locked post'], $this->titles($result->items));
        self::assertTrue($result->items[0]->isLocked());
    }

    public function test_unmatched_filters_return_an_empty_page(): void
    {
        $this->createPost(['title' => 'Only post']);

        $result = $this->repository->find(PostQueryCriteria::forListing(keyword: 'nothing-matches-this'));

        self::assertSame([], $result->items);
        self::assertSame(0, $result->pagination['total']);
        self::assertSame(1, $result->pagination['last_page']);
        self::assertFalse($result->pagination['has_more']);
    }

    public function test_a_post_resolves_by_uuid_and_by_legacy_id(): void
    {
        $post = $this->createPost(['title' => 'Resolvable']);

        $byUuid = $this->repository->findByUuid(EntityId::from($post->uuid));
        $byLegacyId = $this->repository->findByLegacyId($post->id);

        self::assertNotNull($byUuid);
        self::assertNotNull($byLegacyId);
        self::assertSame($post->uuid, $byUuid->getUuid()->value());
        self::assertSame($post->uuid, $byLegacyId->getUuid()->value());
    }

    public function test_unresolvable_identifiers_return_null(): void
    {
        self::assertNull($this->repository->findByUuid(EntityId::from('922f91b4-cbe8-4ca0-8bf8-50de48f5d086')));
        self::assertNull($this->repository->findByLegacyId(999999));
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createPost(array $overrides = []): PersistencePost
    {
        return PersistencePost::factory()->create($overrides);
    }

    private function attachHashtag(PersistencePost $post, string $content, bool $softDeleted = false): void
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
            'deleted_at' => $softDeleted ? now() : null,
        ]);
    }

    private function recordViews(PersistencePost $post, int $count): void
    {
        foreach (range(1, $count) as $index) {
            DB::table('views')->insert([
                'user_id' => User::factory()->create()->id,
                'user_ip' => "127.0.0.{$index}",
                'template_id' => ObjectTemplateType::POST->getLegacyId(),
                'real_object_id' => $post->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @param array<int, DomainPost> $posts
     *
     * @return array<int, string>
     */
    private function titles(array $posts): array
    {
        return array_map(static fn (DomainPost $post): string => $post->getTitle(), $posts);
    }
}
