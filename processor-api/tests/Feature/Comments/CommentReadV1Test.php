<?php

declare(strict_types=1);

namespace Tests\Feature\Comments;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\Comment as PersistenceComment;
use App\Infrastructure\Persistence\Models\Like;
use App\Infrastructure\Persistence\Models\Post as PersistencePost;
use App\Infrastructure\Persistence\Models\Sentence as PersistenceSentence;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
            ->assertJsonPath('items.0.entity_type_uuid', ObjectTemplateType::ARTICLE->value)
            ->assertJsonPath('items.0.entity_type_label', 'Article')
            ->assertJsonPath('items.0.author.id', $author->id)
            ->assertJsonPath('items.0.author.uuid', $author->uuid)
            ->assertJsonPath('items.0.replies_count', 0)
            ->assertJsonPath('items.0.likes_count', 0)
            ->assertJsonPath('items.0.viewer.is_liked', false)
            ->assertJsonPath('items.0.viewer.can_edit', false)
            ->assertJsonPath('items.0.viewer.can_delete', false);
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
            ->assertJsonPath('items.0.viewer.is_liked', true);
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
            ->assertJsonPath('items.0.entity_type_uuid', ObjectTemplateType::LIST->value)
            ->assertJsonPath('items.0.entity_type_label', 'List')
            ->assertJsonPath('items.0.likes_count', 0)
            ->assertJsonPath('items.0.viewer.is_liked', false)
            ->assertJsonPath('items.0.viewer.can_edit', false)
            ->assertJsonPath('items.0.viewer.can_delete', false);
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
    // Post and Sentence reads
    // ========================================

    public function test_guest_can_fetch_post_comments_with_safe_viewer_defaults(): void
    {
        $author = User::factory()->create();
        $post = PersistencePost::factory()->create(['user_id' => $author->id]);
        $comment = $this->createComment(ObjectTemplateType::POST, $post->id, $post->uuid, $author);

        $response = $this->getJson("/api/v1/posts/{$post->uuid}/comments");

        $response->assertOk()
            ->assertJsonMissingPath('success')
            ->assertJsonMissingPath('data')
            ->assertJsonPath('items.0.id', $comment->id)
            ->assertJsonPath('items.0.uuid', $comment->uuid)
            ->assertJsonPath('items.0.entity_uuid', $post->uuid)
            ->assertJsonPath('items.0.entity_type_uuid', ObjectTemplateType::POST->value)
            ->assertJsonPath('items.0.author.id', $author->id)
            ->assertJsonPath('items.0.likes_count', 0)
            ->assertJsonPath('items.0.viewer.is_liked', false)
            ->assertJsonPath('items.0.viewer.can_edit', false)
            ->assertJsonPath('items.0.viewer.can_delete', false);
    }

    public function test_authenticated_viewer_gets_personalized_like_state_for_post_comments(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $post = PersistencePost::factory()->create(['user_id' => $author->id]);
        $comment = $this->createComment(ObjectTemplateType::POST, $post->id, $post->uuid, $author);

        Like::create([
            'user_id' => $viewer->id,
            'template_id' => ObjectTemplateType::COMMENT->getLegacyId(),
            'real_object_id' => $comment->id,
            'value' => true,
        ]);

        Passport::actingAs($viewer, ['*'], 'api');

        $this->getJson("/api/v1/posts/{$post->uuid}/comments")
            ->assertOk()
            ->assertJsonPath('items.0.likes_count', 1)
            ->assertJsonPath('items.0.viewer.is_liked', true);
    }

    public function test_guest_can_fetch_sentence_comments_with_safe_viewer_defaults(): void
    {
        $author = User::factory()->create();
        $sentence = $this->createSentence($author);
        $comment = $this->createComment(ObjectTemplateType::SENTENCE, $sentence->id, $sentence->uuid, $author);

        $response = $this->getJson("/api/v1/sentences/{$sentence->uuid}/comments");

        $response->assertOk()
            ->assertJsonMissingPath('success')
            ->assertJsonMissingPath('data')
            ->assertJsonPath('items.0.id', $comment->id)
            ->assertJsonPath('items.0.entity_uuid', $sentence->uuid)
            ->assertJsonPath('items.0.entity_type_uuid', ObjectTemplateType::SENTENCE->value)
            ->assertJsonPath('items.0.likes_count', 0)
            ->assertJsonPath('items.0.viewer.is_liked', false)
            ->assertJsonPath('items.0.viewer.can_edit', false)
            ->assertJsonPath('items.0.viewer.can_delete', false);
    }

    public function test_authenticated_viewer_gets_personalized_like_state_for_sentence_comments(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $sentence = $this->createSentence($author);
        $comment = $this->createComment(ObjectTemplateType::SENTENCE, $sentence->id, $sentence->uuid, $author);

        Like::create([
            'user_id' => $viewer->id,
            'template_id' => ObjectTemplateType::COMMENT->getLegacyId(),
            'real_object_id' => $comment->id,
            'value' => true,
        ]);

        Passport::actingAs($viewer, ['*'], 'api');

        $this->getJson("/api/v1/sentences/{$sentence->uuid}/comments")
            ->assertOk()
            ->assertJsonPath('items.0.likes_count', 1)
            ->assertJsonPath('items.0.viewer.is_liked', true);
    }

    // ========================================
    // Replies
    // ========================================

    /**
     * A page holds conversations, not rows. A reply is reachable only through
     * the comment it answers, so it can never be paginated away from it.
     */
    public function test_thread_pages_hold_top_level_comments_and_nest_their_replies(): void
    {
        $author = User::factory()->create();
        $post = PersistencePost::factory()->create(['user_id' => $author->id]);
        $parent = $this->createComment(ObjectTemplateType::POST, $post->id, $post->uuid, $author);
        $this->createComment(
            ObjectTemplateType::POST,
            $post->id,
            $post->uuid,
            $author,
            ['parent_comment_id' => $parent->id, 'content' => 'A post reply.'],
        );

        $this->getJson("/api/v1/posts/{$post->uuid}/comments?include_replies=1")
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('items.0.id', $parent->id)
            ->assertJsonPath('items.0.is_reply', false)
            ->assertJsonPath('items.0.replies_count', 1)
            ->assertJsonCount(1, 'items.0.replies')
            ->assertJsonPath('items.0.replies.0.content', 'A post reply.')
            ->assertJsonPath('items.0.replies.0.parent_comment_id', $parent->id)
            ->assertJsonPath('items.0.replies.0.is_reply', true);
    }

    /**
     * The count is what a "show all replies" control renders, so it has to be
     * right even when no replies were asked for. Reporting zero here would be a
     * value the client cannot tell apart from an empty thread.
     */
    public function test_reply_counts_are_reported_even_when_previews_were_not_requested(): void
    {
        $author = User::factory()->create();
        $post = PersistencePost::factory()->create(['user_id' => $author->id]);
        $parent = $this->createComment(ObjectTemplateType::POST, $post->id, $post->uuid, $author);

        foreach (range(1, 3) as $index) {
            $this->createComment(
                ObjectTemplateType::POST,
                $post->id,
                $post->uuid,
                $author,
                ['parent_comment_id' => $parent->id, 'content' => "Reply {$index}."],
            );
        }

        $this->getJson("/api/v1/posts/{$post->uuid}/comments")
            ->assertOk()
            ->assertJsonPath('items.0.replies_count', 3)
            ->assertJsonPath('items.0.replies', []);
    }

    public function test_reply_previews_are_truncated_without_distorting_the_count(): void
    {
        $author = User::factory()->create();
        $post = PersistencePost::factory()->create(['user_id' => $author->id]);
        $parent = $this->createComment(ObjectTemplateType::POST, $post->id, $post->uuid, $author);

        foreach (range(1, 5) as $index) {
            $this->createComment(
                ObjectTemplateType::POST,
                $post->id,
                $post->uuid,
                $author,
                ['parent_comment_id' => $parent->id, 'content' => "Reply {$index}."],
            );
        }

        $this->getJson("/api/v1/posts/{$post->uuid}/comments?include_replies=1&replies_limit=2")
            ->assertOk()
            ->assertJsonCount(2, 'items.0.replies')
            ->assertJsonPath('items.0.replies_count', 5);
    }

    /**
     * Storage allows unlimited nesting; the contract deliberately does not. A
     * reply to a reply joins the same flat subtree as its siblings rather than
     * hiding one level further down.
     */
    public function test_a_reply_to_a_reply_joins_the_same_flat_subtree(): void
    {
        $author = User::factory()->create();
        $post = PersistencePost::factory()->create(['user_id' => $author->id]);
        $parent = $this->createComment(ObjectTemplateType::POST, $post->id, $post->uuid, $author);
        $firstLevel = $this->createComment(
            ObjectTemplateType::POST,
            $post->id,
            $post->uuid,
            $author,
            ['parent_comment_id' => $parent->id, 'content' => 'First level.'],
        );
        $secondLevel = $this->createComment(
            ObjectTemplateType::POST,
            $post->id,
            $post->uuid,
            $author,
            ['parent_comment_id' => $firstLevel->id, 'content' => 'Second level.'],
        );

        $response = $this->getJson("/api/v1/posts/{$post->uuid}/comments?include_replies=1&replies_limit=10")
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.replies_count', 2)
            ->assertJsonCount(2, 'items.0.replies');

        $replies = $response->json('items.0.replies');

        // Oldest first, and the deeper reply still points at its own parent.
        self::assertSame(['First level.', 'Second level.'], array_column($replies, 'content'));
        self::assertSame($firstLevel->id, $replies[1]['parent_comment_id']);
        self::assertSame($secondLevel->id, $replies[1]['id']);
    }

    public function test_reply_enrichment_does_not_grow_with_nesting_depth(): void
    {
        $author = User::factory()->create();
        $shallowPost = PersistencePost::factory()->create(['user_id' => $author->id]);
        $deepPost = PersistencePost::factory()->create(['user_id' => $author->id]);

        $this->buildReplyChain($shallowPost, $author, 2);
        $this->buildReplyChain($deepPost, $author, 5);

        $shallow = $this->countQueriesFor("/api/v1/posts/{$shallowPost->uuid}/comments?include_replies=1&replies_limit=10");
        $deep = $this->countQueriesFor("/api/v1/posts/{$deepPost->uuid}/comments?include_replies=1&replies_limit=10");

        $this->assertSame($shallow, $deep, 'The reply walk issues one query per nesting level.');
    }

    public function test_sort_parameters_reorder_the_thread(): void
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

        $newestFirst = $this->getJson("/api/v1/articles/{$article->uuid}/comments")
            ->assertOk()
            ->json('items.*.content');

        $oldestFirst = $this->getJson("/api/v1/articles/{$article->uuid}/comments?sort_by=created_at&sort_dir=asc")
            ->assertOk()
            ->json('items.*.content');

        self::assertSame(array_reverse($newestFirst), $oldestFirst);
    }

    // ========================================
    // Viewer capabilities
    // ========================================

    public function test_the_author_may_edit_and_delete_their_own_comment(): void
    {
        $author = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $author->id]);
        $this->createComment(ObjectTemplateType::ARTICLE, $article->id, $article->uuid, $author);

        Passport::actingAs($author, ['*'], 'api');

        $this->getJson("/api/v1/articles/{$article->uuid}/comments")
            ->assertOk()
            ->assertJsonPath('items.0.viewer.can_edit', true)
            ->assertJsonPath('items.0.viewer.can_delete', true);
    }

    /**
     * The admin branch of CommentPolicy::canDelete had no client-visible
     * evidence, and the frontend's re-implementation of it silently never
     * fired. It is reported by the API now, and pinned here.
     */
    public function test_an_admin_may_delete_but_not_edit_someone_elses_comment(): void
    {
        $author = User::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::ADMIN->value);
        $article = PersistenceArticle::factory()->create(['user_id' => $author->id]);
        $this->createComment(ObjectTemplateType::ARTICLE, $article->id, $article->uuid, $author);

        Passport::actingAs($admin, ['*'], 'api');

        $this->getJson("/api/v1/articles/{$article->uuid}/comments")
            ->assertOk()
            ->assertJsonPath('items.0.viewer.can_edit', false)
            ->assertJsonPath('items.0.viewer.can_delete', true);
    }

    public function test_an_unrelated_reader_may_neither_edit_nor_delete(): void
    {
        $author = User::factory()->create();
        $stranger = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $author->id]);
        $this->createComment(ObjectTemplateType::ARTICLE, $article->id, $article->uuid, $author);

        Passport::actingAs($stranger, ['*'], 'api');

        $this->getJson("/api/v1/articles/{$article->uuid}/comments")
            ->assertOk()
            ->assertJsonPath('items.0.viewer.can_edit', false)
            ->assertJsonPath('items.0.viewer.can_delete', false);
    }

    /**
     * A comment outlives its author's account. Dropping the whole `author`
     * object is the honest encoding - a name-shaped hole would make the client
     * render an empty attribution.
     */
    public function test_a_comment_whose_author_row_is_gone_is_still_served(): void
    {
        // The article owner and the commenter are different people on purpose:
        // only the commenter's row is removed, so the article survives to be
        // read back.
        $articleOwner = User::factory()->create();
        $commenter = User::factory()->create();
        $article = PersistenceArticle::factory()->create(['user_id' => $articleOwner->id]);
        $comment = $this->createComment(ObjectTemplateType::ARTICLE, $article->id, $article->uuid, $commenter);

        DB::table('users')->where('id', $commenter->id)->delete();

        $this->getJson("/api/v1/articles/{$article->uuid}/comments")
            ->assertOk()
            ->assertJsonPath('items.0.id', $comment->id)
            ->assertJsonPath('items.0.author', null)
            ->assertJsonPath('items.0.viewer.can_edit', false)
            ->assertJsonPath('items.0.viewer.can_delete', false);
    }

    public function test_post_comment_reads_honour_pagination_parameters(): void
    {
        $author = User::factory()->create();
        $post = PersistencePost::factory()->create(['user_id' => $author->id]);

        foreach (range(1, 3) as $index) {
            $this->createComment(
                ObjectTemplateType::POST,
                $post->id,
                $post->uuid,
                $author,
                ['content' => "Post comment number {$index}."],
            );
        }

        $this->getJson("/api/v1/posts/{$post->uuid}/comments?per_page=2&page=2")
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('pagination.page', 2)
            ->assertJsonPath('pagination.per_page', 2)
            ->assertJsonPath('pagination.total', 3)
            ->assertJsonPath('pagination.has_more', false);
    }

    public function test_locked_post_still_serves_its_existing_comments(): void
    {
        $author = User::factory()->create();
        $post = PersistencePost::factory()->locked()->create(['user_id' => $author->id]);
        $comment = $this->createComment(ObjectTemplateType::POST, $post->id, $post->uuid, $author);

        $this->getJson("/api/v1/posts/{$post->uuid}/comments")
            ->assertOk()
            ->assertJsonPath('items.0.id', $comment->id);
    }

    /**
     * A parent that is not there is a plain 404 with the shared body. Neither
     * Post nor Sentence carries a visibility flag today, so "missing" is the
     * only inaccessible case, and the response says nothing beyond the noun.
     */
    public function test_unknown_post_uuid_returns_not_found_for_post_comments(): void
    {
        $this->getJson('/api/v1/posts/'.Str::uuid().'/comments')
            ->assertNotFound()
            ->assertJsonPath('title', 'Not Found')
            ->assertJsonPath('detail', 'Post not found')
            ->assertJsonPath('status', 404);
    }

    public function test_unknown_sentence_uuid_returns_not_found_for_sentence_comments(): void
    {
        $this->getJson('/api/v1/sentences/'.Str::uuid().'/comments')
            ->assertNotFound()
            ->assertJsonPath('title', 'Not Found')
            ->assertJsonPath('detail', 'Sentence not found')
            ->assertJsonPath('status', 404);
    }

    /**
     * Without the route `whereUuid` constraint the segment would reach
     * EntityId::from(), which throws InvalidArgumentException that
     * app/Exceptions/Handler.php does not map - a 500 instead of a 404.
     */
    public function test_malformed_parent_segment_is_not_served_as_a_comment_thread(): void
    {
        $this->getJson('/api/v1/posts/not-a-uuid/comments')->assertNotFound();
        $this->getJson('/api/v1/sentences/not-a-uuid/comments')->assertNotFound();
    }

    /**
     * A legacy numeric id resolves for `GET /v1/posts/{identifier}`, but comment
     * threads are addressed by UUID only. Pinned so a later widening of the
     * route constraint is a deliberate change rather than a side effect.
     */
    public function test_post_comment_reads_do_not_accept_a_legacy_numeric_id(): void
    {
        $author = User::factory()->create();
        $post = PersistencePost::factory()->create(['user_id' => $author->id]);
        $this->createComment(ObjectTemplateType::POST, $post->id, $post->uuid, $author);

        $this->getJson("/api/v1/posts/{$post->id}/comments")->assertNotFound();
    }

    /**
     * Enrichment is bounded: likes count and viewer like state are resolved for
     * the whole page in the same query as the comments, so a five-comment page
     * costs exactly what a one-comment page costs.
     */
    public function test_post_comment_enrichment_does_not_grow_with_page_size(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $post = PersistencePost::factory()->create(['user_id' => $author->id]);

        foreach (range(1, 5) as $index) {
            $comment = $this->createComment(
                ObjectTemplateType::POST,
                $post->id,
                $post->uuid,
                $author,
                ['content' => "Counted comment {$index}."],
            );

            Like::create([
                'user_id' => $viewer->id,
                'template_id' => ObjectTemplateType::COMMENT->getLegacyId(),
                'real_object_id' => $comment->id,
                'value' => true,
            ]);
        }

        Passport::actingAs($viewer, ['*'], 'api');

        // The first authenticated request of a test also loads the viewer's
        // Spatie roles, once. Warm that up so the comparison sees only the
        // per-request cost of the read itself.
        $this->getJson("/api/v1/posts/{$post->uuid}/comments?per_page=1")->assertOk();

        $single = $this->countQueriesFor("/api/v1/posts/{$post->uuid}/comments?per_page=1");
        $page = $this->countQueriesFor("/api/v1/posts/{$post->uuid}/comments?per_page=5");

        $this->assertSame($single, $page, 'Comment read enrichment is not batched.');
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
            ->assertJsonPath('entity_type_uuid', ObjectTemplateType::ARTICLE->value)
            ->assertJsonPath('author.id', $author->id)
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
            ->assertJsonPath('entity_type_uuid', ObjectTemplateType::LIST->value);

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
            ->assertJsonPath('entity_type_uuid', ObjectTemplateType::POST->value)
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

    /**
     * One top-level comment with a chain of replies `$depth` levels deep.
     */
    private function buildReplyChain(PersistencePost $post, User $author, int $depth): void
    {
        $parent = $this->createComment(ObjectTemplateType::POST, $post->id, $post->uuid, $author);
        $parentId = $parent->id;

        foreach (range(1, $depth) as $level) {
            $parentId = $this->createComment(
                ObjectTemplateType::POST,
                $post->id,
                $post->uuid,
                $author,
                ['parent_comment_id' => $parentId, 'content' => "Level {$level}."],
            )->id;
        }
    }

    private function createSentence(User $author): PersistenceSentence
    {
        return PersistenceSentence::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $author->id,
            'tatoeba_entry' => null,
            'content' => 'Sentence content.',
        ]);
    }

    private function countQueriesFor(string $url): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $this->getJson($url)->assertOk();

            return count(DB::getRawQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
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
