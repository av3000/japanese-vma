<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Shared\DTOs\EntityReferenceDTO;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Filament\Resources\Comments\Pages\ListComments;
use App\Filament\Resources\Comments\Pages\ViewComment;
use App\Filament\Resources\Comments\RelationManagers\RepliesRelationManager;
use App\Infrastructure\Persistence\Models\AdminActivity;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\Comment;
use App\Infrastructure\Persistence\Models\Post;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Readers\DatabaseEntityReferenceReader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Feature\Filament\Concerns\InteractsWithAdminPanel;
use Tests\TestCase;

class CommentResourceTest extends TestCase
{
    use InteractsWithAdminPanel;
    use RefreshDatabase;

    private User $admin;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAdminPanel();
        $this->admin = $this->createAdmin();
        $this->author = $this->createPanelUser('Comment Author');
        $this->actingAsPanelUser($this->admin);
    }

    public function test_comments_across_entity_types_show_their_type_and_parent(): void
    {
        $article = Article::factory()->create(['user_id' => $this->author->id, 'title_jp' => '記事のタイトル']);
        $post = Post::factory()->create(['user_id' => $this->author->id, 'title' => 'Post parent']);
        $catalogue = Catalogue::factory()->create(['user_id' => $this->author->id, 'title' => 'List parent']);

        $onArticle = $this->comment(ObjectTemplateType::ARTICLE, $article, 'On the article');
        $onPost = $this->comment(ObjectTemplateType::POST, $post, 'On the post');
        $onList = $this->comment(ObjectTemplateType::LIST, $catalogue, 'On the list');

        Livewire::test(ListComments::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$onArticle, $onPost, $onList])
            ->assertSee('記事のタイトル')
            ->assertSee('Post parent')
            ->assertSee('List parent')
            ->assertSee(ObjectTemplateType::POST->label());
    }

    public function test_comment_on_a_deleted_entity_renders_as_deleted(): void
    {
        $article = Article::factory()->create(['user_id' => $this->author->id]);
        $orphan = $this->comment(ObjectTemplateType::ARTICLE, $article, 'Left behind');
        DB::table('articles')->where('id', $article->id)->delete();

        Livewire::test(ListComments::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$orphan])
            ->assertSee('Deleted article');

        Livewire::test(ViewComment::class, ['record' => $orphan->uuid])
            ->assertOk()
            ->assertSee('Deleted article');
    }

    public function test_reference_reader_runs_one_query_per_entity_type(): void
    {
        $refs = [];

        foreach (range(1, 4) as $index) {
            $refs[] = [ObjectTemplateType::ARTICLE, Article::factory()->create(['user_id' => $this->author->id])->id];
            $refs[] = [ObjectTemplateType::POST, Post::factory()->create(['user_id' => $this->author->id])->id];
            $refs[] = [ObjectTemplateType::LIST, Catalogue::factory()->create(['user_id' => $this->author->id])->id];
        }
        $refs[] = [ObjectTemplateType::ARTICLE, 999999];

        DB::flushQueryLog();
        DB::enableQueryLog();
        $parents = (new DatabaseEntityReferenceReader())->find($refs);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        self::assertSame(3, $queries);
        self::assertCount(13, $parents);
        self::assertFalse($parents[EntityReferenceDTO::key(ObjectTemplateType::ARTICLE, 999999)]->exists());
    }

    public function test_listing_cost_does_not_grow_with_the_page(): void
    {
        $article = Article::factory()->create(['user_id' => $this->author->id]);
        $this->comment(ObjectTemplateType::ARTICLE, $article, 'one');
        // The first render also warms the permission cache; measure from the second.
        Livewire::test(ListComments::class)->assertOk();
        $few = $this->countQueries(fn () => Livewire::test(ListComments::class)->assertOk());

        foreach (range(1, 8) as $index) {
            $other = Article::factory()->create(['user_id' => $this->author->id]);
            $this->comment(ObjectTemplateType::ARTICLE, $other, "comment {$index}");
        }
        $many = $this->countQueries(fn () => Livewire::test(ListComments::class)->assertOk());

        self::assertSame($few, $many);
    }

    public function test_search_and_filters(): void
    {
        $article = Article::factory()->create(['user_id' => $this->author->id]);
        $post = Post::factory()->create(['user_id' => $this->author->id]);
        $parent = $this->comment(ObjectTemplateType::ARTICLE, $article, 'Grammar question');
        $reply = $this->comment(ObjectTemplateType::ARTICLE, $article, 'An answer', ['parent_comment_id' => $parent->id]);
        $onPost = $this->comment(ObjectTemplateType::POST, $post, 'Unrelated chatter');
        $other = $this->createPanelUser('Second voice');
        $byOther = $this->comment(ObjectTemplateType::POST, $post, 'Another view', ['user_id' => $other->id]);

        Livewire::test(ListComments::class)
            ->searchTable('Grammar')
            ->assertCanSeeTableRecords([$parent])
            ->assertCanNotSeeTableRecords([$onPost]);

        Livewire::test(ListComments::class)
            ->filterTable('template_id', ObjectTemplateType::POST->getLegacyId())
            ->assertCanSeeTableRecords([$onPost, $byOther])
            ->assertCanNotSeeTableRecords([$parent]);

        Livewire::test(ListComments::class)
            ->filterTable('user_id', $other->id)
            ->assertCanSeeTableRecords([$byOther])
            ->assertCanNotSeeTableRecords([$onPost]);

        Livewire::test(ListComments::class)
            ->filterTable('has_replies')
            ->assertCanSeeTableRecords([$parent])
            ->assertCanNotSeeTableRecords([$reply, $onPost]);

        Livewire::test(ListComments::class)
            ->filterTable('parent_comment_id', true)
            ->assertCanSeeTableRecords([$reply])
            ->assertCanNotSeeTableRecords([$parent]);
    }

    public function test_view_page_shows_parent_comment_and_replies(): void
    {
        $article = Article::factory()->create(['user_id' => $this->author->id]);
        $parent = $this->comment(ObjectTemplateType::ARTICLE, $article, 'The original remark');
        $reply = $this->comment(ObjectTemplateType::ARTICLE, $article, 'A reply to it', ['parent_comment_id' => $parent->id]);

        Livewire::test(ViewComment::class, ['record' => $reply->uuid])
            ->assertOk()
            ->assertSee('A reply to it')
            ->assertSee('The original remark');

        Livewire::test(RepliesRelationManager::class, ['ownerRecord' => $parent, 'pageClass' => ViewComment::class])
            ->assertOk()
            ->assertCanSeeTableRecords([$reply]);
    }

    public function test_delete_takes_replies_and_records_a_content_snapshot(): void
    {
        $article = Article::factory()->create(['user_id' => $this->author->id]);
        $parent = $this->comment(ObjectTemplateType::ARTICLE, $article, 'Offensive remark');
        $reply = $this->comment(ObjectTemplateType::ARTICLE, $article, 'Reply below it', ['parent_comment_id' => $parent->id]);
        $nested = $this->comment(ObjectTemplateType::ARTICLE, $article, 'Reply to the reply', ['parent_comment_id' => $reply->id]);

        Livewire::test(ViewComment::class, ['record' => $parent->uuid])
            ->assertActionVisible('deleteComment')
            ->callAction('deleteComment')
            ->assertNotified('Comment deleted.')
            ->assertRedirect();

        self::assertNull($parent->fresh());
        self::assertNull($reply->fresh());
        self::assertNull($nested->fresh());

        $row = AdminActivity::query()->sole();
        self::assertSame(ActivityAction::CommentDeleted, $row->action);
        self::assertSame('Offensive remark', $row->changes['before']['content']);
        self::assertSame('Comment Author', $row->changes['before']['author']);
        self::assertSame(['replies_deleted' => 2], $row->context);
    }

    public function test_bulk_delete_of_a_comment_and_its_reply_counts_both_as_deleted(): void
    {
        $article = Article::factory()->create(['user_id' => $this->author->id]);
        $parent = $this->comment(ObjectTemplateType::ARTICLE, $article, 'Parent');
        $reply = $this->comment(ObjectTemplateType::ARTICLE, $article, 'Child', ['parent_comment_id' => $parent->id]);

        Livewire::test(ListComments::class)
            ->callTableBulkAction('bulkDeleteComments', [$reply, $parent])
            ->assertNotified('2 deleted, 0 failed');

        self::assertSame(0, Comment::query()->count());
    }

    public function test_viewers_cannot_delete_and_others_cannot_list(): void
    {
        $article = Article::factory()->create(['user_id' => $this->author->id]);
        $comment = $this->comment(ObjectTemplateType::ARTICLE, $article, 'Visible');

        $this->actingAsPanelUser($this->createUserWithPermissions(['comments.view']));

        Livewire::test(ListComments::class)
            ->assertCanSeeTableRecords([$comment])
            ->assertTableActionHidden('deleteComment', $comment);

        $this->actingAs($this->createUserWithPermissions(['posts.view']), 'web')
            ->get('/admin/comments')
            ->assertForbidden();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function comment(ObjectTemplateType $type, Model $parent, string $content, array $overrides = []): Comment
    {
        return Comment::create([
            'uuid' => (string) Str::uuid(),
            'template_id' => $type->getLegacyId(),
            'real_object_id' => $parent->getKey(),
            'real_object_uuid' => $parent->getAttribute('uuid'),
            'entity_type_uuid' => $type->value,
            'user_id' => $this->author->id,
            'content' => $content,
            ...$overrides,
        ]);
    }

    private function countQueries(callable $render): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $render();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}
