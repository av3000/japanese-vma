<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\Readers;

use App\Application\Comments\Interfaces\Readers\CommentThreadReaderInterface;
use App\Domain\Comments\Models\Comment as DomainComment;
use App\Domain\Comments\Queries\CommentQueryCriteria;
use App\Domain\Comments\ValueObjects\CommentSortCriteria;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Infrastructure\Persistence\Models\Comment as PersistenceComment;
use App\Infrastructure\Persistence\Models\Post as PersistencePost;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

/**
 * The semantic contract every CommentThreadReaderInterface implementation must
 * satisfy. Written against the interface rather than the Eloquent reader, so a
 * future implementation over different storage can be held to the same
 * behaviour.
 *
 * Runs on PostgreSQL, the only supported engine: the reply walk is a recursive
 * CTE with window functions.
 */
class DatabaseCommentThreadReaderContractTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    private CommentThreadReaderInterface $reader;

    private User $author;

    private PersistencePost $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaselineData();

        $this->reader = app(CommentThreadReaderInterface::class);
        $this->author = User::factory()->create();
        $this->post = PersistencePost::factory()->create(['user_id' => $this->author->id]);
    }

    // ---- roots

    public function test_the_root_page_excludes_replies_so_the_total_counts_conversations(): void
    {
        $root = $this->comment();
        $this->comment(['parent_comment_id' => $root->id]);
        $this->comment(['parent_comment_id' => $root->id]);

        $page = $this->rootPage();

        $this->assertCount(1, $page->comments, 'Replies must not appear as page entries');
        $this->assertSame(1, $page->pagination->total, 'The total counts conversations, not rows');
    }

    public function test_roots_of_another_entity_are_excluded(): void
    {
        $this->comment(['content' => 'Mine.']);

        $otherPost = PersistencePost::factory()->create(['user_id' => $this->author->id]);
        $this->comment(['content' => 'Theirs.', 'real_object_id' => $otherPost->id]);

        $this->assertSame(['Mine.'], $this->contentsOf($this->rootPage()->comments));
    }

    public function test_sorting_is_deterministic_when_the_primary_value_ties(): void
    {
        $sharedTimestamp = now()->subDay();

        foreach (['A.', 'B.', 'C.'] as $content) {
            $this->comment(['content' => $content, 'created_at' => $sharedTimestamp]);
        }

        $first = $this->contentsOf($this->rootPage()->comments);
        $second = $this->contentsOf($this->rootPage()->comments);

        $this->assertSame($first, $second, 'Ties must not reorder between identical requests');
    }

    public function test_the_sort_direction_is_honoured(): void
    {
        $this->comment(['content' => 'Older.', 'created_at' => now()->subDays(2)]);
        $this->comment(['content' => 'Newer.', 'created_at' => now()->subDay()]);

        $this->assertSame(
            ['Newer.', 'Older.'],
            $this->contentsOf($this->rootPage()->comments),
        );

        $ascending = $this->rootPage(new CommentQueryCriteria(
            sort: CommentSortCriteria::from('created_at', 'asc'),
            pagination: Pagination::default(),
        ));

        $this->assertSame(['Older.', 'Newer.'], $this->contentsOf($ascending->comments));
    }

    public function test_root_pagination_reports_the_page_it_returned(): void
    {
        foreach (range(1, 3) as $index) {
            $this->comment(['content' => "Root {$index}."]);
        }

        $page = $this->rootPage(new CommentQueryCriteria(
            sort: CommentSortCriteria::default(),
            pagination: new Pagination(1, 2),
        ));

        $this->assertCount(2, $page->comments);
        $this->assertSame(3, $page->pagination->total);
        $this->assertSame(2, $page->pagination->lastPage);
        $this->assertTrue($page->pagination->hasMore);
    }

    // ---- reply previews

    public function test_each_root_gets_its_own_count_and_previews(): void
    {
        $quiet = $this->comment();
        $busy = $this->comment();
        $childless = $this->comment();

        $this->comment(['parent_comment_id' => $quiet->id, 'content' => 'Quiet reply.']);

        foreach (range(1, 5) as $index) {
            $this->comment(['parent_comment_id' => $busy->id, 'content' => "Busy reply {$index}."]);
        }

        $previews = $this->reader->replyPreviews([$quiet->id, $busy->id, $childless->id], 2, null);

        $this->assertSame(1, $previews[$quiet->id]->count);
        $this->assertSame(['Quiet reply.'], $this->contentsOf($previews[$quiet->id]->replies));

        $this->assertSame(5, $previews[$busy->id]->count, 'The count is the whole subtree, not the preview');
        $this->assertSame(
            ['Busy reply 1.', 'Busy reply 2.'],
            $this->contentsOf($previews[$busy->id]->replies),
            'Previews are the oldest replies, bounded by the limit',
        );

        $this->assertArrayNotHasKey($childless->id, $previews, 'A root with no replies is absent');
    }

    public function test_the_count_includes_replies_at_every_depth(): void
    {
        $root = $this->comment();
        $child = $this->comment(['parent_comment_id' => $root->id]);
        $grandchild = $this->comment(['parent_comment_id' => $child->id]);
        $this->comment(['parent_comment_id' => $grandchild->id]);

        $previews = $this->reader->replyPreviews([$root->id], 0, null);

        $this->assertSame(3, $previews[$root->id]->count);
    }

    public function test_a_zero_limit_returns_counts_without_previews(): void
    {
        $root = $this->comment();
        $this->comment(['parent_comment_id' => $root->id]);
        $this->comment(['parent_comment_id' => $root->id]);

        $previews = $this->reader->replyPreviews([$root->id], 0, null);

        $this->assertSame(2, $previews[$root->id]->count);
        $this->assertSame([], $previews[$root->id]->replies);
    }

    public function test_no_roots_means_no_query_result(): void
    {
        $this->assertSame([], $this->reader->replyPreviews([], 3, null));
    }

    // ---- reply page

    public function test_the_reply_page_is_flat_oldest_first_and_spans_depths(): void
    {
        $root = $this->comment();
        $child = $this->comment(['parent_comment_id' => $root->id, 'content' => 'Child.']);
        $this->comment(['parent_comment_id' => $child->id, 'content' => 'Grandchild.']);

        $page = $this->reader->replyPage($root->id, Pagination::default(), null);

        $this->assertSame(['Child.', 'Grandchild.'], $this->contentsOf($page->comments));
        $this->assertSame(2, $page->pagination->total);
    }

    public function test_the_reply_page_is_cut_without_losing_the_total(): void
    {
        $root = $this->comment();

        foreach (range(1, 5) as $index) {
            $this->comment(['parent_comment_id' => $root->id, 'content' => "Reply {$index}."]);
        }

        $first = $this->reader->replyPage($root->id, new Pagination(1, 2), null);

        $this->assertSame(['Reply 1.', 'Reply 2.'], $this->contentsOf($first->comments));
        $this->assertSame(5, $first->pagination->total);
        $this->assertSame(3, $first->pagination->lastPage);
        $this->assertTrue($first->pagination->hasMore);

        $last = $this->reader->replyPage($root->id, new Pagination(3, 2), null);

        $this->assertSame(['Reply 5.'], $this->contentsOf($last->comments));
        $this->assertFalse($last->pagination->hasMore, 'The final page must not advertise more');
    }

    /**
     * A page past the end still has to report how big the thread is; a caller
     * that overshoots must not be told the conversation is empty.
     */
    public function test_a_page_past_the_end_keeps_the_real_total(): void
    {
        $root = $this->comment();

        foreach (range(1, 3) as $index) {
            $this->comment(['parent_comment_id' => $root->id, 'content' => "Reply {$index}."]);
        }

        $page = $this->reader->replyPage($root->id, new Pagination(9, 2), null);

        $this->assertSame([], $page->comments);
        $this->assertSame(3, $page->pagination->total);
        $this->assertFalse($page->pagination->hasMore);
    }

    public function test_a_childless_root_returns_an_empty_reply_page(): void
    {
        $page = $this->reader->replyPage($this->comment()->id, Pagination::default(), null);

        $this->assertSame([], $page->comments);
        $this->assertSame(0, $page->pagination->total);
        $this->assertFalse($page->pagination->hasMore);
    }

    // ---- viewer enrichment

    public function test_a_guest_sees_no_viewer_like_state(): void
    {
        $root = $this->comment();
        $this->comment(['parent_comment_id' => $root->id]);

        $page = $this->reader->replyPage($root->id, Pagination::default(), null);

        $this->assertFalse($page->comments[0]->isLikedByViewer());
    }

    // ---- helpers

    /**
     * @param array<string, mixed> $overrides
     */
    private function comment(array $overrides = []): PersistenceComment
    {
        return PersistenceComment::create(array_merge([
            'uuid' => (string) Str::uuid(),
            'template_id' => ObjectTemplateType::POST->getLegacyId(),
            'real_object_id' => $this->post->id,
            'real_object_uuid' => $this->post->uuid,
            'entity_type_uuid' => ObjectTemplateType::POST->value,
            'user_id' => $this->author->id,
            'parent_comment_id' => null,
            'content' => 'Test comment content.',
        ], $overrides));
    }

    private function rootPage(?CommentQueryCriteria $criteria = null)
    {
        return $this->reader->rootPage(
            entityId: $this->post->id,
            entityType: ObjectTemplateType::POST,
            criteria: $criteria ?? CommentQueryCriteria::default(),
            viewerUserId: null,
        );
    }

    /**
     * @param array<int, DomainComment> $comments
     *
     * @return array<int, string>
     */
    private function contentsOf(array $comments): array
    {
        return array_map(
            static fn (DomainComment $comment): string => $comment->getContent(),
            $comments,
        );
    }
}
