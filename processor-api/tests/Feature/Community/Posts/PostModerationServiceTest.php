<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Posts;

use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Community\Posts\Interfaces\Repositories\PostRepositoryInterface;
use App\Application\Community\Posts\Services\PostModerationServiceInterface;
use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Auth\Providers\PassportCurrentUserProvider;
use App\Infrastructure\Persistence\Models\AdminActivity;
use App\Infrastructure\Persistence\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Filament\Concerns\InteractsWithAdminPanel;
use Tests\Support\FailingActivityRecorder;
use Tests\Support\InjectsWriteFailures;
use Tests\TestCase;

/**
 * PostModerationService at its own seam. Removal records the activity row before deleting, so a
 * delete that fails afterwards must take the row back with it.
 */
final class PostModerationServiceTest extends TestCase
{
    use InjectsWriteFailures;
    use InteractsWithAdminPanel;
    use RefreshDatabase;

    private AuthenticatedUser $staff;

    private AuthenticatedUser $member;

    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAdminPanel();
        $this->staff = PassportCurrentUserProvider::authenticatedUserFor($this->createAdmin());
        $this->member = PassportCurrentUserProvider::authenticatedUserFor($this->createPanelUser('Member'));
        $this->post = Post::factory()->create(['user_id' => $this->createPanelUser('Author')->id, 'locked' => false]);
    }

    public function test_locking_is_refused_for_non_staff(): void
    {
        $result = $this->service()->setLocked($this->uuid(), true, $this->member);

        self::assertSame('POST_ACCESS_DENIED', $result->getError()->code);
        $this->assertUnchangedWithoutActivity();
    }

    public function test_locking_an_unknown_post_is_not_found(): void
    {
        $result = $this->service()->setLocked(EntityId::generate(), true, $this->staff);

        self::assertSame('POST_NOT_FOUND', $result->getError()->code);
    }

    public function test_locking_writes_one_row(): void
    {
        $result = $this->service()->setLocked($this->uuid(), true, $this->staff);

        self::assertTrue($result->isSuccess());
        self::assertTrue($this->post->fresh()->locked);
        $row = AdminActivity::query()->sole();
        self::assertSame(ActivityAction::PostLocked, $row->action);
        self::assertSame($this->post->uuid, $row->subject_id);
        self::assertSame(['locked' => false], $row->changes['before']);
    }

    public function test_locking_rolls_back_when_the_activity_row_fails(): void
    {
        $this->app->instance(ActivityRecorderInterface::class, new FailingActivityRecorder);

        $result = $this->service()->setLocked($this->uuid(), true, $this->staff);

        self::assertSame('POST_LOCK_UPDATE_FAILED', $result->getError()->code);
        $this->assertUnchangedWithoutActivity();
    }

    public function test_a_failed_lock_write_leaves_no_activity_row(): void
    {
        $this->failOn(PostRepositoryInterface::class, 'setLocked');

        $result = $this->service()->setLocked($this->uuid(), true, $this->staff);

        self::assertSame('POST_LOCK_UPDATE_FAILED', $result->getError()->code);
        $this->assertUnchangedWithoutActivity();
    }

    public function test_removal_is_refused_for_non_staff(): void
    {
        $result = $this->service()->remove($this->uuid(), $this->member);

        self::assertSame('POST_ACCESS_DENIED', $result->getError()->code);
        $this->assertUnchangedWithoutActivity();
    }

    public function test_removal_of_an_unknown_post_is_not_found(): void
    {
        $result = $this->service()->remove(EntityId::generate(), $this->staff);

        self::assertSame('POST_NOT_FOUND', $result->getError()->code);
    }

    public function test_removal_deletes_and_writes_one_row_with_a_snapshot(): void
    {
        $result = $this->service()->remove($this->uuid(), $this->staff);

        self::assertTrue($result->isSuccess());
        self::assertNull($this->post->fresh());
        $row = AdminActivity::query()->sole();
        self::assertSame(ActivityAction::PostDeleted, $row->action);
        self::assertSame($this->post->title, $row->changes['before']['title']);
    }

    public function test_removal_rolls_back_when_the_activity_row_fails(): void
    {
        $this->app->instance(ActivityRecorderInterface::class, new FailingActivityRecorder);

        $result = $this->service()->remove($this->uuid(), $this->staff);

        self::assertSame('POST_DELETION_FAILED', $result->getError()->code);
        $this->assertUnchangedWithoutActivity();
    }

    public function test_a_failed_delete_after_the_row_leaves_no_activity_row(): void
    {
        $this->failOn(PostRepositoryInterface::class, 'delete');

        $result = $this->service()->remove($this->uuid(), $this->staff);

        self::assertSame('POST_DELETION_FAILED', $result->getError()->code);
        $this->assertUnchangedWithoutActivity();
    }

    private function service(): PostModerationServiceInterface
    {
        return app(PostModerationServiceInterface::class);
    }

    private function uuid(): EntityId
    {
        return new EntityId((string) $this->post->uuid);
    }

    private function assertUnchangedWithoutActivity(): void
    {
        $fresh = $this->post->fresh();

        self::assertNotNull($fresh);
        self::assertFalse($fresh->locked);
        self::assertSame(0, AdminActivity::query()->count());
    }
}
