<?php

declare(strict_types=1);

namespace Tests\Feature\Comments;

use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Models\Comment as PersistenceComment;
use App\Infrastructure\Persistence\Models\Like;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Repositories\CommentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

/**
 * UUID-addressed comment update and delete.
 *
 * Update is owner-only: editing rewrites someone's words, so admins are not
 * granted it. Delete is owner-or-admin and takes the whole reply subtree with
 * it, since a reply is meaningless once its parent is gone.
 */
class CommentMutationV1Test extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
    }

    // ========================================
    // Update
    // ========================================

    public function test_owner_can_update_their_comment_by_uuid(): void
    {
        $owner = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $owner->id]);
        $comment = $this->createComment($article, $owner, ['content' => 'Original content.']);
        Passport::actingAs($owner, ['*'], 'api');

        $response = $this->putJson("/api/v1/comments/{$comment->uuid}", [
            'content' => 'Edited content.',
        ]);

        $response->assertOk()
            ->assertJsonMissingPath('success')
            ->assertJsonMissingPath('data')
            ->assertJsonPath('uuid', $comment->uuid)
            ->assertJsonPath('content', 'Edited content.')
            ->assertJsonPath('author_id', $owner->id);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Edited content.',
        ]);
    }

    public function test_update_does_not_move_the_comment_to_another_entity(): void
    {
        $owner = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $owner->id]);
        $comment = $this->createComment($article, $owner);
        Passport::actingAs($owner, ['*'], 'api');

        $this->putJson("/api/v1/comments/{$comment->uuid}", [
            'content' => 'Edited content.',
            'entity_id' => 4242,
            'entity_uuid' => (string) Str::uuid(),
        ])->assertOk();

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'real_object_id' => $article->id,
            'real_object_uuid' => $article->uuid,
        ]);
    }

    public function test_non_owner_cannot_update_a_comment(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $owner->id]);
        $comment = $this->createComment($article, $owner, ['content' => 'Original content.']);
        Passport::actingAs($stranger, ['*'], 'api');

        $this->putJson("/api/v1/comments/{$comment->uuid}", [
            'content' => 'Hijacked content.',
        ])->assertForbidden()
            ->assertJsonPath('title', 'Access denied');

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Original content.',
        ]);
    }

    public function test_admin_cannot_update_someone_elses_comment(): void
    {
        $owner = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $owner->id]);
        $comment = $this->createComment($article, $owner, ['content' => 'Original content.']);
        Passport::actingAs($this->createAdmin(), ['*'], 'api');

        $this->putJson("/api/v1/comments/{$comment->uuid}", [
            'content' => 'Moderated content.',
        ])->assertForbidden();

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Original content.',
        ]);
    }

    public function test_guest_cannot_update_a_comment(): void
    {
        $owner = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $owner->id]);
        $comment = $this->createComment($article, $owner);

        $this->putJson("/api/v1/comments/{$comment->uuid}", [
            'content' => 'Guest edit.',
        ])->assertStatus(401);
    }

    public function test_update_of_an_unknown_comment_uuid_returns_not_found(): void
    {
        Passport::actingAs(User::factory()->create(), ['*'], 'api');

        $this->putJson('/api/v1/comments/'.Str::uuid(), [
            'content' => 'Nothing to edit.',
        ])->assertNotFound()
            ->assertJsonPath('title', 'Comment not found');
    }

    public function test_update_rejects_content_outside_the_allowed_length(): void
    {
        $owner = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $owner->id]);
        $comment = $this->createComment($article, $owner);
        Passport::actingAs($owner, ['*'], 'api');

        $this->putJson("/api/v1/comments/{$comment->uuid}", ['content' => 'a'])
            ->assertStatus(422)
            ->assertJsonPath('errors.content.0', 'The content must be at least 2 characters.');

        $this->putJson("/api/v1/comments/{$comment->uuid}", ['content' => str_repeat('a', 1001)])
            ->assertStatus(422);

        $this->putJson("/api/v1/comments/{$comment->uuid}", [])
            ->assertStatus(422)
            ->assertJsonPath('errors.content.0', 'The content field is required.');
    }

    // ========================================
    // Delete
    // ========================================

    public function test_owner_can_delete_their_comment_by_uuid(): void
    {
        $owner = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $owner->id]);
        $comment = $this->createComment($article, $owner);
        Passport::actingAs($owner, ['*'], 'api');

        $this->deleteJson("/api/v1/comments/{$comment->uuid}")->assertNoContent();

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_admin_can_delete_someone_elses_comment(): void
    {
        $owner = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $owner->id]);
        $comment = $this->createComment($article, $owner);
        Passport::actingAs($this->createAdmin(), ['*'], 'api');

        $this->deleteJson("/api/v1/comments/{$comment->uuid}")->assertNoContent();

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_non_owner_cannot_delete_a_comment(): void
    {
        $owner = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $owner->id]);
        $comment = $this->createComment($article, $owner);
        Passport::actingAs(User::factory()->create(), ['*'], 'api');

        $this->deleteJson("/api/v1/comments/{$comment->uuid}")
            ->assertForbidden()
            ->assertJsonPath('title', 'Access denied');

        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }

    public function test_guest_cannot_delete_a_comment(): void
    {
        $owner = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $owner->id]);
        $comment = $this->createComment($article, $owner);

        $this->deleteJson("/api/v1/comments/{$comment->uuid}")->assertStatus(401);

        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }

    /**
     * The legacy parent-specific routes stay live during frontend migration and
     * write comments without real_object_uuid, so the new UUID mutations must
     * still work on those rows instead of failing to map them.
     */
    public function test_owner_can_mutate_a_legacy_comment_that_has_no_entity_uuid(): void
    {
        $owner = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $owner->id]);
        $comment = $this->createComment($article, $owner, ['real_object_uuid' => null]);
        Passport::actingAs($owner, ['*'], 'api');

        $this->putJson("/api/v1/comments/{$comment->uuid}", ['content' => 'Edited legacy comment.'])
            ->assertOk()
            ->assertJsonPath('entity_uuid', null)
            ->assertJsonPath('content', 'Edited legacy comment.');

        $this->deleteJson("/api/v1/comments/{$comment->uuid}")->assertNoContent();

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_delete_of_an_unknown_comment_uuid_returns_not_found(): void
    {
        Passport::actingAs(User::factory()->create(), ['*'], 'api');

        $this->deleteJson('/api/v1/comments/'.Str::uuid())
            ->assertNotFound()
            ->assertJsonPath('title', 'Comment not found');
    }

    // ========================================
    // Delete - cascade
    // ========================================

    public function test_delete_removes_the_whole_reply_subtree_at_any_depth(): void
    {
        $owner = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $owner->id]);

        $root = $this->createComment($article, $owner, ['content' => 'Root.']);
        $reply = $this->createComment($article, $owner, ['content' => 'Reply.', 'parent_comment_id' => $root->id]);
        $nested = $this->createComment($article, $owner, ['content' => 'Nested.', 'parent_comment_id' => $reply->id]);
        $deepest = $this->createComment($article, $owner, ['content' => 'Deepest.', 'parent_comment_id' => $nested->id]);
        $sibling = $this->createComment($article, $owner, ['content' => 'Unrelated sibling.']);

        Passport::actingAs($owner, ['*'], 'api');

        $this->deleteJson("/api/v1/comments/{$root->uuid}")->assertNoContent();

        foreach ([$root, $reply, $nested, $deepest] as $deleted) {
            $this->assertDatabaseMissing('comments', ['id' => $deleted->id]);
        }

        $this->assertDatabaseHas('comments', ['id' => $sibling->id]);
    }

    public function test_delete_removes_likes_of_the_comment_and_of_every_descendant(): void
    {
        $owner = User::factory()->create();
        $liker = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $owner->id]);

        $root = $this->createComment($article, $owner);
        $reply = $this->createComment($article, $owner, ['parent_comment_id' => $root->id]);
        $nested = $this->createComment($article, $owner, ['parent_comment_id' => $reply->id]);
        $sibling = $this->createComment($article, $owner);

        foreach ([$root, $reply, $nested, $sibling] as $target) {
            $this->createCommentLike($target, $liker);
        }

        // Same numeric id, different template: belongs to an unrelated object.
        Like::create([
            'user_id' => $liker->id,
            'template_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'real_object_id' => $root->id,
            'value' => true,
        ]);

        Passport::actingAs($owner, ['*'], 'api');

        $this->deleteJson("/api/v1/comments/{$root->uuid}")->assertNoContent();

        foreach ([$root, $reply, $nested] as $deleted) {
            $this->assertDatabaseMissing('likes', [
                'template_id' => ObjectTemplateType::COMMENT->getLegacyId(),
                'real_object_id' => $deleted->id,
            ]);
        }

        $this->assertDatabaseHas('likes', [
            'template_id' => ObjectTemplateType::COMMENT->getLegacyId(),
            'real_object_id' => $sibling->id,
        ]);
        $this->assertDatabaseHas('likes', [
            'template_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'real_object_id' => $root->id,
        ]);
    }

    public function test_a_failure_midway_through_the_cascade_rolls_the_whole_delete_back(): void
    {
        $owner = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $owner->id]);

        $root = $this->createComment($article, $owner);
        $reply = $this->createComment($article, $owner, ['parent_comment_id' => $root->id]);
        $this->createCommentLike($root, $owner);
        $this->createCommentLike($reply, $owner);

        // Deletes the descendants, then fails before the root is removed.
        $this->app->instance(
            CommentRepositoryInterface::class,
            new FailsAfterDescendantDeleteCommentRepository(
                $this->app->make(CommentRepository::class)
            )
        );

        Passport::actingAs($owner, ['*'], 'api');

        $this->deleteJson("/api/v1/comments/{$root->uuid}")
            ->assertStatus(500)
            ->assertJsonPath('title', 'Comment deletion failed');

        $this->assertDatabaseHas('comments', ['id' => $root->id]);
        $this->assertDatabaseHas('comments', ['id' => $reply->id]);
        $this->assertDatabaseHas('likes', [
            'template_id' => ObjectTemplateType::COMMENT->getLegacyId(),
            'real_object_id' => $reply->id,
        ]);
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::ADMIN->value);

        return $admin;
    }

    private function createComment(
        PersistenceArticle $article,
        User $author,
        array $overrides = [],
    ): PersistenceComment {
        return PersistenceComment::create(array_merge([
            'uuid' => (string) Str::uuid(),
            'template_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'real_object_id' => $article->id,
            'real_object_uuid' => $article->uuid,
            'entity_type_uuid' => ObjectTemplateType::ARTICLE->value,
            'user_id' => $author->id,
            'parent_comment_id' => null,
            'content' => 'Test comment content.',
        ], $overrides));
    }

    private function createCommentLike(PersistenceComment $comment, User $user): void
    {
        Like::create([
            'user_id' => $user->id,
            'template_id' => ObjectTemplateType::COMMENT->getLegacyId(),
            'real_object_id' => $comment->id,
            'value' => true,
        ]);
    }
}
