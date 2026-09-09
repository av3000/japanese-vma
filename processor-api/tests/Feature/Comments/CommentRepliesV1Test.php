<?php

declare(strict_types=1);

namespace Tests\Feature\Comments;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Infrastructure\Persistence\Models\Comment as PersistenceComment;
use App\Infrastructure\Persistence\Models\Like;
use App\Infrastructure\Persistence\Models\Post as PersistencePost;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

/**
 * `GET /api/v1/comments/{uuid}/replies`.
 *
 * Thread reads carry a bounded preview; this endpoint serves the rest of one
 * comment's subtree once that preview is not enough.
 */
class CommentRepliesV1Test extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
    }

    public function test_replies_are_paginated_oldest_first(): void
    {
        $author = User::factory()->create();
        $post = PersistencePost::factory()->create(['user_id' => $author->id]);
        $parent = $this->createComment($post, $author);

        foreach (range(1, 3) as $index) {
            $this->createComment($post, $author, [
                'parent_comment_id' => $parent->id,
                'content' => "Reply {$index}.",
            ]);
        }

        $this->getJson("/api/v1/comments/{$parent->uuid}/replies?per_page=2")
            ->assertOk()
            ->assertJsonCount(2, 'items')
            ->assertJsonPath('items.0.content', 'Reply 1.')
            ->assertJsonPath('items.1.content', 'Reply 2.')
            ->assertJsonPath('pagination.total', 3)
            ->assertJsonPath('pagination.has_more', true);

        $this->getJson("/api/v1/comments/{$parent->uuid}/replies?per_page=2&page=2")
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.content', 'Reply 3.')
            ->assertJsonPath('pagination.has_more', false);
    }

    /**
     * The whole subtree, not one level: a reply to a reply is part of the same
     * conversation and would otherwise be unreachable.
     */
    public function test_replies_include_every_depth_of_the_subtree(): void
    {
        $author = User::factory()->create();
        $post = PersistencePost::factory()->create(['user_id' => $author->id]);
        $parent = $this->createComment($post, $author);
        $firstLevel = $this->createComment($post, $author, [
            'parent_comment_id' => $parent->id,
            'content' => 'First level.',
        ]);
        $this->createComment($post, $author, [
            'parent_comment_id' => $firstLevel->id,
            'content' => 'Second level.',
        ]);

        $response = $this->getJson("/api/v1/comments/{$parent->uuid}/replies")
            ->assertOk()
            ->assertJsonCount(2, 'items')
            ->assertJsonPath('pagination.total', 2);

        self::assertSame(
            ['First level.', 'Second level.'],
            array_column($response->json('items'), 'content'),
        );
    }

    /**
     * A reply cannot carry replies of its own in this contract, so the item
     * shape has no `replies` key to be permanently empty.
     */
    public function test_reply_items_do_not_carry_a_nested_replies_key(): void
    {
        $author = User::factory()->create();
        $post = PersistencePost::factory()->create(['user_id' => $author->id]);
        $parent = $this->createComment($post, $author);
        $this->createComment($post, $author, ['parent_comment_id' => $parent->id]);

        $reply = $this->getJson("/api/v1/comments/{$parent->uuid}/replies")
            ->assertOk()
            ->json('items.0');

        self::assertArrayNotHasKey('replies', $reply);
        self::assertArrayNotHasKey('replies_count', $reply);
    }

    public function test_a_comment_without_replies_returns_an_empty_page(): void
    {
        $author = User::factory()->create();
        $post = PersistencePost::factory()->create(['user_id' => $author->id]);
        $comment = $this->createComment($post, $author);

        $this->getJson("/api/v1/comments/{$comment->uuid}/replies")
            ->assertOk()
            ->assertJsonPath('items', [])
            ->assertJsonPath('pagination.total', 0)
            ->assertJsonPath('pagination.has_more', false);
    }

    public function test_a_guest_gets_safe_viewer_defaults(): void
    {
        $author = User::factory()->create();
        $post = PersistencePost::factory()->create(['user_id' => $author->id]);
        $parent = $this->createComment($post, $author);
        $this->createComment($post, $author, ['parent_comment_id' => $parent->id]);

        $this->getJson("/api/v1/comments/{$parent->uuid}/replies")
            ->assertOk()
            ->assertJsonPath('items.0.viewer.is_liked', false)
            ->assertJsonPath('items.0.viewer.can_edit', false)
            ->assertJsonPath('items.0.viewer.can_delete', false);
    }

    public function test_an_authenticated_reader_gets_personalized_reply_state(): void
    {
        $author = User::factory()->create();
        $post = PersistencePost::factory()->create(['user_id' => $author->id]);
        $parent = $this->createComment($post, $author);
        $reply = $this->createComment($post, $author, ['parent_comment_id' => $parent->id]);

        Like::create([
            'user_id' => $author->id,
            'template_id' => ObjectTemplateType::COMMENT->getLegacyId(),
            'real_object_id' => $reply->id,
            'value' => true,
        ]);

        Passport::actingAs($author, ['*'], 'api');

        $this->getJson("/api/v1/comments/{$parent->uuid}/replies")
            ->assertOk()
            ->assertJsonPath('items.0.likes_count', 1)
            ->assertJsonPath('items.0.viewer.is_liked', true)
            ->assertJsonPath('items.0.viewer.can_edit', true)
            ->assertJsonPath('items.0.viewer.can_delete', true);
    }

    public function test_unknown_comment_uuid_returns_not_found(): void
    {
        $this->getJson('/api/v1/comments/'.Str::uuid().'/replies')
            ->assertNotFound()
            ->assertJsonPath('status', 404);
    }

    /**
     * `whereUuid` keeps a malformed segment away from EntityId::from(), which
     * throws an unmapped InvalidArgumentException - a 500 where the reader
     * should see a 404.
     */
    public function test_malformed_comment_segment_is_not_served(): void
    {
        $this->getJson('/api/v1/comments/not-a-uuid/replies')->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createComment(PersistencePost $post, User $author, array $overrides = []): PersistenceComment
    {
        return PersistenceComment::create(array_merge([
            'uuid' => (string) Str::uuid(),
            'template_id' => ObjectTemplateType::POST->getLegacyId(),
            'real_object_id' => $post->id,
            'real_object_uuid' => $post->uuid,
            'entity_type_uuid' => ObjectTemplateType::POST->value,
            'user_id' => $author->id,
            'parent_comment_id' => null,
            'content' => 'Test comment content.',
        ], $overrides));
    }
}
