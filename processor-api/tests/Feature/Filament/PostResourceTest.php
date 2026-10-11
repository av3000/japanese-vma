<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Community\Posts\Services\PostWriteServiceInterface;
use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Community\Posts\DTOs\PostCreateDTO;
use App\Domain\Community\Posts\DTOs\PostUpdateDTO;
use App\Domain\Community\Posts\Enums\PostTopic;
use App\Domain\Community\Posts\Errors\PostErrors;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Viewer;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Posts\Pages\ViewPost;
use App\Filament\Resources\Posts\RelationManagers\CommentsRelationManager;
use App\Infrastructure\Persistence\Models\AdminActivity;
use App\Infrastructure\Persistence\Models\Comment;
use App\Infrastructure\Persistence\Models\Post;
use App\Infrastructure\Persistence\Models\User;
use App\Shared\Results\Result;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Livewire\Livewire;
use Tests\Feature\Filament\Concerns\InteractsWithAdminPanel;
use Tests\TestCase;

class PostResourceTest extends TestCase
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
        $this->author = $this->createPanelUser('Post Author');
        $this->actingAsPanelUser($this->admin);
    }

    public function test_posts_can_be_listed_searched_and_filtered(): void
    {
        $locked = $this->createPost(['title' => 'Locked question', 'locked' => true, 'type' => (string) PostTopic::FAQ->value]);
        $open = $this->createPost(['title' => 'Open thread', 'content' => 'Talking about particles']);
        $other = $this->createPanelUser('Someone else');
        $byOther = $this->createPost(['title' => 'Other thread', 'user_id' => $other->id]);

        Livewire::test(ListPosts::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$locked, $open, $byOther]);

        Livewire::test(ListPosts::class)
            ->searchTable('particles')
            ->assertCanSeeTableRecords([$open])
            ->assertCanNotSeeTableRecords([$locked]);

        Livewire::test(ListPosts::class)
            ->filterTable('locked', true)
            ->assertCanSeeTableRecords([$locked])
            ->assertCanNotSeeTableRecords([$open]);

        Livewire::test(ListPosts::class)
            ->filterTable('type', (string) PostTopic::FAQ->value)
            ->assertCanSeeTableRecords([$locked])
            ->assertCanNotSeeTableRecords([$open]);

        Livewire::test(ListPosts::class)
            ->filterTable('user_id', $other->id)
            ->assertCanSeeTableRecords([$byOther])
            ->assertCanNotSeeTableRecords([$open]);
    }

    public function test_view_page_shows_the_post_and_its_comments(): void
    {
        $post = $this->createPost(['title' => 'Viewed post', 'content' => 'Body of the post']);
        $comment = $this->comment($post, 'First comment');

        Livewire::test(ViewPost::class, ['record' => $post->uuid])
            ->assertOk()
            ->assertSee('Body of the post')
            ->assertSee('Post Author')
            ->assertSee('comments are closed');

        Livewire::test(CommentsRelationManager::class, ['ownerRecord' => $post, 'pageClass' => ViewPost::class])
            ->assertOk()
            ->assertCanSeeTableRecords([$comment]);
    }

    public function test_lock_and_unlock_go_through_the_write_service(): void
    {
        $post = $this->createPost();

        Livewire::test(ViewPost::class, ['record' => $post->uuid])
            ->callAction('toggleLock')
            ->assertNotified('Post locked.');

        self::assertTrue($post->fresh()->locked);

        Livewire::test(ViewPost::class, ['record' => $post->uuid])
            ->callAction('toggleLock')
            ->assertNotified('Post unlocked.');

        self::assertFalse($post->fresh()->locked);
        self::assertSame(
            [ActivityAction::PostLocked, ActivityAction::PostUnlocked],
            AdminActivity::query()->orderBy('id')->pluck('action')->all(),
        );
    }

    public function test_bulk_lock_reports_each_outcome(): void
    {
        $first = $this->createPost();
        $second = $this->createPost(['locked' => true]);

        Livewire::test(ListPosts::class)
            ->callTableBulkAction('bulkLock', [$first, $second])
            ->assertNotified('2 locked, 0 failed');

        self::assertTrue($first->fresh()->locked);
        self::assertTrue($second->fresh()->locked);
        self::assertSame(1, AdminActivity::query()->count());
    }

    public function test_bulk_lock_reports_partial_failures(): void
    {
        $kept = $this->createPost();
        $refused = $this->createPost(['title' => 'Refused post']);
        $real = app(PostWriteServiceInterface::class);
        $failingUuid = (string) $refused->uuid;

        $this->app->instance(PostWriteServiceInterface::class, new class($real, $failingUuid) implements PostWriteServiceInterface
        {
            public function __construct(private PostWriteServiceInterface $inner, private string $failingUuid)
            {
            }

            public function create(PostCreateDTO $dto, AuthenticatedUser $actor, Viewer $viewer): Result
            {
                return $this->inner->create($dto, $actor, $viewer);
            }

            public function update(EntityId $uuid, PostUpdateDTO $dto, AuthenticatedUser $actor): Result
            {
                return $this->inner->update($uuid, $dto, $actor);
            }

            public function delete(EntityId $uuid, AuthenticatedUser $actor): Result
            {
                return $this->inner->delete($uuid, $actor);
            }

            public function setLocked(EntityId $uuid, bool $locked, AuthenticatedUser $actor): Result
            {
                return $uuid->value() === $this->failingUuid
                    ? Result::failure(PostErrors::lockUpdateFailed())
                    : $this->inner->setLocked($uuid, $locked, $actor);
            }
        });

        Livewire::test(ListPosts::class)
            ->callTableBulkAction('bulkLock', [$kept, $refused])
            ->assertNotified('1 locked, 1 failed');

        self::assertTrue($kept->fresh()->locked);
        self::assertFalse($refused->fresh()->locked);
    }

    public function test_delete_cleans_up_comments_and_records_a_snapshot(): void
    {
        $post = $this->createPost(['title' => 'Removed post', 'content' => 'Original words']);
        $this->comment($post, 'Will be removed');

        Livewire::test(ViewPost::class, ['record' => $post->uuid])
            ->callAction('deletePost')
            ->assertNotified('Post deleted.')
            ->assertRedirect();

        self::assertNull($post->fresh());
        self::assertSame(0, Comment::query()->where('real_object_id', $post->id)->where('template_id', ObjectTemplateType::POST->getLegacyId())->count());

        $row = AdminActivity::query()->sole();
        self::assertSame(ActivityAction::PostDeleted, $row->action);
        self::assertSame('Original words', $row->changes['before']['content']);
        self::assertSame('Post Author', $row->changes['before']['author']);
    }

    public function test_author_deleting_own_post_over_the_api_is_not_logged(): void
    {
        $post = $this->createPost();
        Passport::actingAs($this->author, ['*'], 'api');

        $this->deleteJson("/api/v1/posts/{$post->uuid}")->assertSuccessful();

        self::assertSame(0, AdminActivity::query()->count());
    }

    public function test_viewers_see_posts_without_moderation_actions(): void
    {
        $post = $this->createPost();
        $this->actingAsPanelUser($this->createUserWithPermissions(['posts.view']));

        Livewire::test(ListPosts::class)
            ->assertOk()
            ->assertTableActionHidden('toggleLock', $post);

        Livewire::test(ViewPost::class, ['record' => $post->uuid])
            ->assertActionHidden('toggleLock')
            ->assertActionHidden('deletePost');
    }

    public function test_posts_are_forbidden_without_posts_view(): void
    {
        $this->actingAs($this->createUserWithPermissions(['articles.view']), 'web')
            ->get('/admin/posts')
            ->assertForbidden();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createPost(array $attributes = []): Post
    {
        return Post::factory()->create(['user_id' => $this->author->id, ...$attributes]);
    }

    private function comment(Post $post, string $content): Comment
    {
        return Comment::create([
            'uuid' => (string) Str::uuid(),
            'template_id' => ObjectTemplateType::POST->getLegacyId(),
            'real_object_id' => $post->id,
            'real_object_uuid' => $post->uuid,
            'entity_type_uuid' => ObjectTemplateType::POST->value,
            'user_id' => $this->author->id,
            'content' => $content,
        ]);
    }
}
