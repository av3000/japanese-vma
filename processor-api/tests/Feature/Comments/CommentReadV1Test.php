<?php

declare(strict_types=1);

namespace Tests\Feature\Comments;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Models\Catalogue;
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
 * Article and Catalogue comment reads plus the generic create route. Both
 * predate the shared Comment boundary, so this file pins their contract while
 * mutations move into the application layer.
 */
class CommentReadV1Test extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
    }

    // ========================================
    // Reads
    // ========================================

    public function test_guest_can_fetch_article_comments_with_safe_viewer_defaults(): void
    {
        $author = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $author->id]);
        $comment = $this->createComment(ObjectTemplateType::ARTICLE, $article->id, $article->uuid, $author);

        $response = $this->getJson("/api/v1/articles/{$article->uuid}/comments");

        $response->assertOk()
            ->assertJsonMissingPath('success')
            ->assertJsonMissingPath('data')
            ->assertJsonPath('items.0.id', $comment->id)
            ->assertJsonPath('items.0.uuid', $comment->uuid)
            ->assertJsonPath('items.0.entity_type', 'article')
            ->assertJsonPath('items.0.likes_count', 0)
            ->assertJsonPath('items.0.is_liked_by_viewer', false);
    }

    public function test_authenticated_viewer_gets_personalized_like_state_for_article_comments(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $author->id]);
        $comment = $this->createComment(ObjectTemplateType::ARTICLE, $article->id, $article->uuid, $author);

        Like::create([
            'user_id' => $viewer->id,
            'template_id' => ObjectTemplateType::COMMENT->getLegacyId(),
            'real_object_id' => $comment->id,
            'value' => true,
        ]);

        Passport::actingAs($viewer, ['*'], 'api');

        $response = $this->getJson("/api/v1/articles/{$article->uuid}/comments");

        $response->assertOk()
            ->assertJsonPath('items.0.id', $comment->id)
            ->assertJsonPath('items.0.likes_count', 1)
            ->assertJsonPath('items.0.is_liked_by_viewer', true);
    }

    public function test_guest_can_fetch_list_comments_by_catalogue_uuid(): void
    {
        $author = User::factory()->create();
        $catalogue = Catalogue::factory()->create(['user_id' => $author->id]);
        $comment = $this->createComment(ObjectTemplateType::LIST, $catalogue->id, $catalogue->uuid, $author);

        $response = $this->getJson("/api/v1/catalogues/{$catalogue->uuid}/comments");

        $response->assertOk()
            ->assertJsonMissingPath('success')
            ->assertJsonMissingPath('data')
            ->assertJsonPath('items.0.id', $comment->id)
            ->assertJsonPath('items.0.entity_uuid', $catalogue->uuid)
            ->assertJsonPath('items.0.entity_type', 'list')
            ->assertJsonPath('items.0.likes_count', 0)
            ->assertJsonPath('items.0.is_liked_by_viewer', false);
    }

    public function test_unknown_article_uuid_returns_not_found_for_article_comments(): void
    {
        $response = $this->getJson('/api/v1/articles/'.Str::uuid().'/comments');

        $response->assertNotFound()
            ->assertJsonPath('title', 'Not Found')
            ->assertJsonPath('detail', 'Article not found')
            ->assertJsonPath('status', 404);
    }

    public function test_unknown_catalogue_uuid_returns_not_found_for_list_comments(): void
    {
        $response = $this->getJson('/api/v1/catalogues/'.Str::uuid().'/comments');

        $response->assertNotFound()
            ->assertJsonPath('title', 'Not Found')
            ->assertJsonPath('detail', 'Catalogue not found')
            ->assertJsonPath('status', 404);
    }

    public function test_entity_without_comments_returns_an_empty_page_rather_than_an_error(): void
    {
        $author = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $author->id]);

        $response = $this->getJson("/api/v1/articles/{$article->uuid}/comments");

        $response->assertOk()
            ->assertJsonPath('items', [])
            ->assertJsonPath('pagination.total', 0)
            ->assertJsonPath('pagination.page', 1)
            ->assertJsonPath('pagination.last_page', 1)
            ->assertJsonPath('pagination.has_more', false);
    }

    public function test_reads_honour_pagination_parameters(): void
    {
        $author = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $author->id]);

        foreach (range(1, 3) as $index) {
            $this->createComment(
                ObjectTemplateType::ARTICLE,
                $article->id,
                $article->uuid,
                $author,
                ['content' => "Comment number {$index}."],
            );
        }

        $response = $this->getJson("/api/v1/articles/{$article->uuid}/comments?per_page=2&page=2");

        $response->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('pagination.page', 2)
            ->assertJsonPath('pagination.per_page', 2)
            ->assertJsonPath('pagination.total', 3)
            ->assertJsonPath('pagination.has_more', false);
    }

    // ========================================
    // Generic create
    // ========================================

    public function test_authenticated_user_can_create_article_comment_through_generic_v1_route(): void
    {
        $author = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $author->id]);
        Passport::actingAs($author, ['*'], 'api');

        $response = $this->postJson('/api/v1/comments', [
            'entity_type' => ObjectTemplateType::ARTICLE->value,
            'entity_id' => $article->id,
            'entity_uuid' => $article->uuid,
            'content' => 'New v1 article comment.',
        ]);

        $response->assertCreated()
            ->assertJsonMissingPath('success')
            ->assertJsonMissingPath('data')
            ->assertJsonPath('entity_uuid', $article->uuid)
            ->assertJsonPath('entity_type', 'article')
            ->assertJsonPath('author_id', $author->id)
            ->assertJsonPath('content', 'New v1 article comment.')
            ->assertJsonPath('is_reply', false);

        $this->assertDatabaseHas('comments', [
            'uuid' => $response->json('uuid'),
            'template_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'real_object_id' => $article->id,
            'real_object_uuid' => $article->uuid,
            'entity_type_uuid' => ObjectTemplateType::ARTICLE->value,
            'user_id' => $author->id,
        ]);
    }

    public function test_authenticated_user_can_create_catalogue_comment_through_generic_v1_route_and_read_it_back(): void
    {
        $author = User::factory()->create();
        $catalogue = Catalogue::factory()->create(['user_id' => $author->id]);
        Passport::actingAs($author, ['*'], 'api');

        $this->postJson('/api/v1/comments', [
            'entity_type' => ObjectTemplateType::LIST->value,
            'entity_id' => $catalogue->id,
            'entity_uuid' => $catalogue->uuid,
            'content' => 'New v1 catalogue comment.',
        ])->assertCreated()
            ->assertJsonPath('entity_type', 'list');

        $this->getJson("/api/v1/catalogues/{$catalogue->uuid}/comments")
            ->assertOk()
            ->assertJsonPath('items.0.content', 'New v1 catalogue comment.');
    }

    public function test_authenticated_user_can_create_post_comment_through_generic_v1_route(): void
    {
        $author = User::factory()->create();
        $post = PersistencePost::factory()->create(['user_id' => $author->id]);
        Passport::actingAs($author, ['*'], 'api');

        $this->postJson('/api/v1/comments', [
            'entity_type' => ObjectTemplateType::POST->value,
            'entity_id' => $post->id,
            'entity_uuid' => $post->uuid,
            'content' => 'New v1 post comment.',
        ])->assertCreated()
            ->assertJsonPath('entity_type', 'post')
            ->assertJsonPath('entity_uuid', $post->uuid);

        $this->assertDatabaseHas('comments', [
            'template_id' => ObjectTemplateType::POST->getLegacyId(),
            'real_object_id' => $post->id,
            'user_id' => $author->id,
        ]);
    }

    public function test_authenticated_user_can_reply_to_an_existing_comment(): void
    {
        $author = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $author->id]);
        $parent = $this->createComment(ObjectTemplateType::ARTICLE, $article->id, $article->uuid, $author);
        Passport::actingAs($author, ['*'], 'api');

        $this->postJson('/api/v1/comments', [
            'entity_type' => ObjectTemplateType::ARTICLE->value,
            'entity_id' => $article->id,
            'entity_uuid' => $article->uuid,
            'content' => 'A reply.',
            'parent_comment_id' => $parent->id,
        ])->assertCreated()
            ->assertJsonPath('parent_comment_id', $parent->id)
            ->assertJsonPath('is_reply', true);
    }

    public function test_replies_may_themselves_be_replied_to(): void
    {
        $author = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $author->id]);
        $parent = $this->createComment(ObjectTemplateType::ARTICLE, $article->id, $article->uuid, $author);
        $reply = $this->createComment(
            ObjectTemplateType::ARTICLE,
            $article->id,
            $article->uuid,
            $author,
            ['parent_comment_id' => $parent->id],
        );
        Passport::actingAs($author, ['*'], 'api');

        $this->postJson('/api/v1/comments', [
            'entity_type' => ObjectTemplateType::ARTICLE->value,
            'entity_id' => $article->id,
            'entity_uuid' => $article->uuid,
            'content' => 'A nested reply.',
            'parent_comment_id' => $reply->id,
        ])->assertCreated()
            ->assertJsonPath('parent_comment_id', $reply->id)
            ->assertJsonPath('is_reply', true);
    }

    public function test_guest_cannot_post_comments_to_protected_route(): void
    {
        $author = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $author->id]);

        $this->postJson('/api/v1/comments', [
            'entity_type' => ObjectTemplateType::ARTICLE->value,
            'entity_id' => $article->id,
            'entity_uuid' => $article->uuid,
            'content' => 'Guest comment attempt.',
        ])->assertStatus(401);
    }

    // ========================================
    // Create - parent integrity
    // ========================================

    public function test_unknown_entity_type_returns_validation_error(): void
    {
        Passport::actingAs(User::factory()->create(), ['*'], 'api');

        $this->postJson('/api/v1/comments', [
            'entity_type' => (string) Str::uuid(),
            'entity_id' => 1,
            'entity_uuid' => (string) Str::uuid(),
            'content' => 'Unsupported target.',
        ])->assertStatus(422)
            ->assertJsonPath('errors.entity_type.0', 'The selected entity type is invalid.');
    }

    public function test_entity_type_without_a_comment_surface_is_rejected(): void
    {
        Passport::actingAs(User::factory()->create(), ['*'], 'api');

        $this->postJson('/api/v1/comments', [
            'entity_type' => ObjectTemplateType::KANJI->value,
            'entity_id' => 1,
            'entity_uuid' => (string) Str::uuid(),
            'content' => 'Kanji comments are not a thing.',
        ])->assertStatus(422)
            ->assertJsonPath('title', 'Unsupported entity type');
    }

    public function test_entity_id_is_required_for_generic_v1_comment_create(): void
    {
        $author = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $author->id]);
        Passport::actingAs($author, ['*'], 'api');

        $this->postJson('/api/v1/comments', [
            'entity_type' => ObjectTemplateType::ARTICLE->value,
            'entity_uuid' => $article->uuid,
            'content' => 'Missing entity id.',
        ])->assertStatus(422)
            ->assertJsonPath('errors.entity_id.0', 'The entity id field is required.');
    }

    public function test_entity_uuid_must_be_a_valid_uuid_for_generic_v1_comment_create(): void
    {
        Passport::actingAs(User::factory()->create(), ['*'], 'api');

        $this->postJson('/api/v1/comments', [
            'entity_type' => ObjectTemplateType::ARTICLE->value,
            'entity_id' => 123,
            'entity_uuid' => 'not-a-uuid',
            'content' => 'Bad uuid.',
        ])->assertStatus(422)
            ->assertJsonPath('errors.entity_uuid.0', 'The entity uuid must be a valid UUID.');
    }

    public function test_create_against_a_missing_entity_returns_not_found(): void
    {
        Passport::actingAs(User::factory()->create(), ['*'], 'api');

        $this->postJson('/api/v1/comments', [
            'entity_type' => ObjectTemplateType::ARTICLE->value,
            'entity_id' => 4242,
            'entity_uuid' => (string) Str::uuid(),
            'content' => 'Nothing to comment on.',
        ])->assertNotFound()
            ->assertJsonPath('detail', 'Article not found');

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_create_rejects_an_entity_id_that_does_not_match_the_entity_uuid(): void
    {
        $author = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $author->id]);
        $other = PersistenceArticle::factory()->create(['user_id' => $author->id]);
        Passport::actingAs($author, ['*'], 'api');

        $this->postJson('/api/v1/comments', [
            'entity_type' => ObjectTemplateType::ARTICLE->value,
            'entity_id' => $other->id,
            'entity_uuid' => $article->uuid,
            'content' => 'Mismatched identifiers.',
        ])->assertStatus(422)
            ->assertJsonPath('title', 'Entity identity mismatch');

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_create_rejects_a_parent_comment_that_does_not_exist(): void
    {
        $author = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $author->id]);
        Passport::actingAs($author, ['*'], 'api');

        $this->postJson('/api/v1/comments', [
            'entity_type' => ObjectTemplateType::ARTICLE->value,
            'entity_id' => $article->id,
            'entity_uuid' => $article->uuid,
            'content' => 'Reply to nothing.',
            'parent_comment_id' => 9999,
        ])->assertStatus(422)
            ->assertJsonPath('title', 'Parent comment not found');

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_create_rejects_a_parent_comment_belonging_to_another_entity(): void
    {
        $author = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $author->id]);
        $catalogue = Catalogue::factory()->create(['user_id' => $author->id]);
        $foreignParent = $this->createComment(
            ObjectTemplateType::LIST,
            $catalogue->id,
            $catalogue->uuid,
            $author,
        );
        Passport::actingAs($author, ['*'], 'api');

        $this->postJson('/api/v1/comments', [
            'entity_type' => ObjectTemplateType::ARTICLE->value,
            'entity_id' => $article->id,
            'entity_uuid' => $article->uuid,
            'content' => 'Cross-entity reply.',
            'parent_comment_id' => $foreignParent->id,
        ])->assertStatus(422)
            ->assertJsonPath('title', 'Parent comment belongs to another entity');

        $this->assertDatabaseCount('comments', 1);
    }

    private function createComment(
        ObjectTemplateType $entityType,
        int $entityId,
        string $entityUuid,
        User $author,
        array $overrides = [],
    ): PersistenceComment {
        return PersistenceComment::create(array_merge([
            'uuid' => (string) Str::uuid(),
            'template_id' => $entityType->getLegacyId(),
            'real_object_id' => $entityId,
            'real_object_uuid' => $entityUuid,
            'entity_type_uuid' => $entityType->value,
            'user_id' => $author->id,
            'parent_comment_id' => null,
            'content' => 'Test comment content.',
        ], $overrides));
    }
}
