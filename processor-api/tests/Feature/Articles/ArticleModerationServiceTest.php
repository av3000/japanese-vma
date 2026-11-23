<?php

declare(strict_types=1);

namespace Tests\Feature\Articles;

use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Application\Articles\Actions\Deletion\DeleteArticleAction;
use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Application\Articles\Services\ArticleModerationServiceInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Auth\Providers\PassportCurrentUserProvider;
use App\Infrastructure\Persistence\Models\AdminActivity;
use App\Infrastructure\Persistence\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Filament\Concerns\InteractsWithAdminPanel;
use Tests\Support\FailingActivityRecorder;
use Tests\Support\InjectsWriteFailures;
use Tests\TestCase;

/**
 * ArticleModerationService at its own seam, without the panel: who may act, what happens to an
 * unknown uuid, and that the change and its single activity row commit or roll back together.
 */
final class ArticleModerationServiceTest extends TestCase
{
    use InjectsWriteFailures;
    use InteractsWithAdminPanel;
    use RefreshDatabase;

    private AuthenticatedUser $staff;

    private AuthenticatedUser $member;

    private Article $article;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAdminPanel();
        $this->staff = PassportCurrentUserProvider::authenticatedUserFor($this->createAdmin());
        $this->member = PassportCurrentUserProvider::authenticatedUserFor($this->createPanelUser('Member'));
        $this->article = Article::factory()->create([
            'user_id' => $this->createPanelUser('Author')->id,
            'status' => ArticleStatus::PENDING,
            'publicity' => PublicityStatus::PUBLIC,
        ]);
    }

    public function test_status_change_is_refused_for_non_staff(): void
    {
        $result = $this->service()->updateStatus($this->uuid(), ArticleStatus::REJECTED, $this->member);

        self::assertSame('Articles.ModerationAccessDenied', $result->getError()->code);
        $this->assertUnchangedWithoutActivity();
    }

    public function test_status_change_of_an_unknown_article_is_not_found(): void
    {
        $result = $this->service()->updateStatus(EntityId::generate(), ArticleStatus::REJECTED, $this->staff);

        self::assertSame('Articles.NotFound', $result->getError()->code);
        self::assertSame(0, AdminActivity::query()->count());
    }

    public function test_status_change_writes_one_row(): void
    {
        $result = $this->service()->updateStatus($this->uuid(), ArticleStatus::REJECTED, $this->staff, 'off topic');

        self::assertTrue($result->isSuccess());
        self::assertSame(ArticleStatus::REJECTED, $this->article->fresh()->status);
        $row = AdminActivity::query()->sole();
        self::assertSame(ActivityAction::ArticleStatusChanged, $row->action);
        self::assertSame($this->article->uuid, $row->subject_id);
        self::assertSame(['status' => ArticleStatus::PENDING->label()], $row->changes['before']);
        self::assertSame(['reason' => 'off topic'], $row->context);
    }

    public function test_status_change_rolls_back_when_the_activity_row_fails(): void
    {
        $this->app->instance(ActivityRecorderInterface::class, new FailingActivityRecorder);

        $result = $this->service()->updateStatus($this->uuid(), ArticleStatus::REJECTED, $this->staff);

        self::assertSame('Articles.ModerationStatusUpdateFailed', $result->getError()->code);
        $this->assertUnchangedWithoutActivity();
    }

    public function test_a_failed_status_write_leaves_no_activity_row(): void
    {
        $this->failOn(ArticleRepositoryInterface::class, 'updateStatus');

        $result = $this->service()->updateStatus($this->uuid(), ArticleStatus::REJECTED, $this->staff);

        self::assertSame('Articles.ModerationStatusUpdateFailed', $result->getError()->code);
        $this->assertUnchangedWithoutActivity();
    }

    public function test_publicity_change_is_refused_for_non_staff(): void
    {
        $result = $this->service()->setPublicity($this->uuid(), PublicityStatus::PRIVATE, $this->member);

        self::assertSame('Articles.ModerationAccessDenied', $result->getError()->code);
        $this->assertUnchangedWithoutActivity();
    }

    public function test_publicity_change_of_an_unknown_article_is_not_found(): void
    {
        $result = $this->service()->setPublicity(EntityId::generate(), PublicityStatus::PRIVATE, $this->staff);

        self::assertSame('Articles.NotFound', $result->getError()->code);
    }

    public function test_publicity_change_writes_one_row(): void
    {
        $result = $this->service()->setPublicity($this->uuid(), PublicityStatus::PRIVATE, $this->staff);

        self::assertTrue($result->isSuccess());
        self::assertSame(PublicityStatus::PRIVATE, $this->article->fresh()->publicity);
        $row = AdminActivity::query()->sole();
        self::assertSame(ActivityAction::ArticlePublicityChanged, $row->action);
        self::assertSame(['publicity' => PublicityStatus::PUBLIC->label()], $row->changes['before']);
    }

    public function test_publicity_change_rolls_back_when_the_activity_row_fails(): void
    {
        $this->app->instance(ActivityRecorderInterface::class, new FailingActivityRecorder);

        $result = $this->service()->setPublicity($this->uuid(), PublicityStatus::PRIVATE, $this->staff);

        self::assertSame('Articles.PublicityUpdateFailed', $result->getError()->code);
        $this->assertUnchangedWithoutActivity();
    }

    public function test_a_failed_publicity_write_leaves_no_activity_row(): void
    {
        $this->failOn(ArticleRepositoryInterface::class, 'updatePublicity');

        $result = $this->service()->setPublicity($this->uuid(), PublicityStatus::PRIVATE, $this->staff);

        self::assertSame('Articles.PublicityUpdateFailed', $result->getError()->code);
        $this->assertUnchangedWithoutActivity();
    }

    public function test_removal_is_refused_for_non_staff(): void
    {
        $result = $this->service()->remove($this->uuid(), $this->member);

        self::assertSame('Articles.ModerationAccessDenied', $result->getError()->code);
        $this->assertUnchangedWithoutActivity();
    }

    public function test_removal_of_an_unknown_article_is_not_found(): void
    {
        $result = $this->service()->remove(EntityId::generate(), $this->staff);

        self::assertSame('Articles.NotFound', $result->getError()->code);
    }

    public function test_removal_deletes_and_writes_one_row_with_a_snapshot(): void
    {
        $result = $this->service()->remove($this->uuid(), $this->staff);

        self::assertTrue($result->isSuccess());
        self::assertNull($this->article->fresh());
        $row = AdminActivity::query()->sole();
        self::assertSame(ActivityAction::ArticleDeleted, $row->action);
        self::assertSame($this->article->title_jp, $row->changes['before']['title']);
    }

    public function test_removal_rolls_back_when_the_activity_row_fails(): void
    {
        $this->app->instance(ActivityRecorderInterface::class, new FailingActivityRecorder);

        $result = $this->service()->remove($this->uuid(), $this->staff);

        self::assertSame('Articles.DeletionFailed', $result->getError()->code);
        $this->assertUnchangedWithoutActivity();
    }

    public function test_a_failed_delete_after_the_row_leaves_no_activity_row(): void
    {
        $this->failOn(DeleteArticleAction::class, 'execute');

        $result = $this->service()->remove($this->uuid(), $this->staff);

        self::assertSame('Articles.DeletionFailed', $result->getError()->code);
        $this->assertUnchangedWithoutActivity();
    }

    private function service(): ArticleModerationServiceInterface
    {
        return app(ArticleModerationServiceInterface::class);
    }

    private function uuid(): EntityId
    {
        return new EntityId((string) $this->article->uuid);
    }

    private function assertUnchangedWithoutActivity(): void
    {
        $fresh = $this->article->fresh();

        self::assertNotNull($fresh);
        self::assertSame(ArticleStatus::PENDING, $fresh->status);
        self::assertSame(PublicityStatus::PUBLIC, $fresh->publicity);
        self::assertSame(0, AdminActivity::query()->count());
    }
}
