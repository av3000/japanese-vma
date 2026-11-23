<?php

declare(strict_types=1);

namespace Tests\Feature\Comments;

use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Application\Comments\Services\CommentModerationServiceInterface;
use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Auth\Providers\PassportCurrentUserProvider;
use App\Infrastructure\Persistence\Models\AdminActivity;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\Comment;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Feature\Filament\Concerns\InteractsWithAdminPanel;
use Tests\Support\FailingActivityRecorder;
use Tests\TestCase;

/**
 * CommentModerationService at its own seam. Removing a comment takes its replies with it, so a
 * failure anywhere in the transaction must bring the replies back too.
 */
final class CommentModerationServiceTest extends TestCase
{
    use InteractsWithAdminPanel;
    use RefreshDatabase;

    private AuthenticatedUser $staff;

    private AuthenticatedUser $member;

    private User $author;

    private Comment $comment;

    private Comment $reply;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAdminPanel();
        $this->staff = PassportCurrentUserProvider::authenticatedUserFor($this->createAdmin());
        $this->member = PassportCurrentUserProvider::authenticatedUserFor($this->createPanelUser('Member'));
        $this->author = $this->createPanelUser('Commenter');
        $article = Article::factory()->create(['user_id' => $this->author->id]);
        $this->comment = $this->comment($article, 'Root comment');
        $this->reply = $this->comment($article, 'A reply', parent: $this->comment);
    }

    public function test_removal_is_refused_for_non_staff(): void
    {
        $result = $this->service()->remove($this->uuid(), $this->member);

        self::assertSame('Comments.AccessDenied', $result->getError()->code);
        $this->assertThreadIntactWithoutActivity();
    }

    public function test_removal_of_an_unknown_comment_is_not_found(): void
    {
        $result = $this->service()->remove(EntityId::generate(), $this->staff);

        self::assertSame('Comments.NotFound', $result->getError()->code);
    }

    public function test_removal_takes_the_replies_and_writes_one_row(): void
    {
        $result = $this->service()->remove($this->uuid(), $this->staff);

        self::assertTrue($result->isSuccess());
        self::assertNull($this->comment->fresh());
        self::assertNull($this->reply->fresh());
        $row = AdminActivity::query()->sole();
        self::assertSame(ActivityAction::CommentDeleted, $row->action);
        self::assertSame($this->comment->uuid, $row->subject_id);
        self::assertSame('Root comment', $row->changes['before']['content']);
    }

    public function test_removal_rolls_back_the_replies_when_the_activity_row_fails(): void
    {
        $this->app->instance(ActivityRecorderInterface::class, new FailingActivityRecorder);

        $result = $this->service()->remove($this->uuid(), $this->staff);

        self::assertSame('Comments.DeletionFailed', $result->getError()->code);
        $this->assertThreadIntactWithoutActivity();
    }

    public function test_a_delete_that_fails_after_the_replies_brings_them_back(): void
    {
        $this->app->instance(
            CommentRepositoryInterface::class,
            new FailsAfterDescendantDeleteCommentRepository(app(CommentRepositoryInterface::class)),
        );

        $result = $this->service()->remove($this->uuid(), $this->staff);

        self::assertSame('Comments.DeletionFailed', $result->getError()->code);
        $this->assertThreadIntactWithoutActivity();
    }

    private function service(): CommentModerationServiceInterface
    {
        return app(CommentModerationServiceInterface::class);
    }

    private function uuid(): EntityId
    {
        return new EntityId((string) $this->comment->uuid);
    }

    private function comment(Article $article, string $content, ?Comment $parent = null): Comment
    {
        return Comment::create([
            'uuid' => (string) Str::uuid(),
            'template_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'real_object_id' => $article->id,
            'real_object_uuid' => $article->uuid,
            'entity_type_uuid' => ObjectTemplateType::ARTICLE->value,
            'user_id' => $this->author->id,
            'content' => $content,
            'parent_comment_id' => $parent?->id,
        ]);
    }

    private function assertThreadIntactWithoutActivity(): void
    {
        self::assertNotNull($this->comment->fresh());
        self::assertNotNull($this->reply->fresh());
        self::assertSame(0, AdminActivity::query()->count());
    }
}
