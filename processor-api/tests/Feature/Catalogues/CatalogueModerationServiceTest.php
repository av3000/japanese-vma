<?php

declare(strict_types=1);

namespace Tests\Feature\Catalogues;

use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Catalogues\Actions\DeleteCatalogueAction;
use App\Application\Catalogues\Interfaces\Repositories\CatalogueRepositoryInterface;
use App\Application\Catalogues\Services\CatalogueModerationServiceInterface;
use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Auth\Providers\PassportCurrentUserProvider;
use App\Infrastructure\Persistence\Models\AdminActivity;
use App\Infrastructure\Persistence\Models\Catalogue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Filament\Concerns\InteractsWithAdminPanel;
use Tests\Support\FailingActivityRecorder;
use Tests\Support\InjectsWriteFailures;
use Tests\TestCase;

/**
 * CatalogueModerationService at its own seam: who may act, the Known-list rule, and that the
 * change and its single activity row commit or roll back together.
 */
final class CatalogueModerationServiceTest extends TestCase
{
    use InjectsWriteFailures;
    use InteractsWithAdminPanel;
    use RefreshDatabase;

    private AuthenticatedUser $staff;

    private AuthenticatedUser $member;

    private Catalogue $catalogue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAdminPanel();
        $this->staff = PassportCurrentUserProvider::authenticatedUserFor($this->createAdmin());
        $this->member = PassportCurrentUserProvider::authenticatedUserFor($this->createPanelUser('Member'));
        $this->catalogue = Catalogue::factory()->ofType(SavedListType::ARTICLES)->create([
            'user_id' => $this->createPanelUser('Owner')->id,
            'publicity' => true,
        ]);
    }

    public function test_publicity_change_is_refused_for_non_staff(): void
    {
        $result = $this->service()->setPublicity($this->uuid(), PublicityStatus::PRIVATE, $this->member);

        self::assertSame('Catalogues.AccessDenied', $result->getError()->code);
        $this->assertUnchangedWithoutActivity();
    }

    public function test_publicity_change_of_an_unknown_catalogue_is_not_found(): void
    {
        $result = $this->service()->setPublicity(EntityId::generate(), PublicityStatus::PRIVATE, $this->staff);

        self::assertSame('Catalogues.NotFound', $result->getError()->code);
    }

    public function test_publicity_change_writes_one_row(): void
    {
        $result = $this->service()->setPublicity($this->uuid(), PublicityStatus::PRIVATE, $this->staff);

        self::assertTrue($result->isSuccess());
        self::assertFalse($this->catalogue->fresh()->publicity);
        $row = AdminActivity::query()->sole();
        self::assertSame(ActivityAction::CataloguePublicityChanged, $row->action);
        self::assertSame($this->catalogue->uuid, $row->subject_id);
        self::assertSame(['publicity' => PublicityStatus::PUBLIC->label()], $row->changes['before']);
    }

    public function test_publicity_change_rolls_back_when_the_activity_row_fails(): void
    {
        $this->app->instance(ActivityRecorderInterface::class, new FailingActivityRecorder);

        $result = $this->service()->setPublicity($this->uuid(), PublicityStatus::PRIVATE, $this->staff);

        self::assertSame('Catalogues.PublicityUpdateFailed', $result->getError()->code);
        $this->assertUnchangedWithoutActivity();
    }

    public function test_a_failed_publicity_write_leaves_no_activity_row(): void
    {
        $this->failOn(CatalogueRepositoryInterface::class, 'updatePublicity');

        $result = $this->service()->setPublicity($this->uuid(), PublicityStatus::PRIVATE, $this->staff);

        self::assertSame('Catalogues.PublicityUpdateFailed', $result->getError()->code);
        $this->assertUnchangedWithoutActivity();
    }

    public function test_removal_is_refused_for_non_staff_whether_or_not_the_uuid_exists(): void
    {
        self::assertSame('Catalogues.AccessDenied', $this->service()->remove($this->uuid(), $this->member)->getError()->code);
        self::assertSame('Catalogues.AccessDenied', $this->service()->remove(EntityId::generate(), $this->member)->getError()->code);
        $this->assertUnchangedWithoutActivity();
    }

    public function test_removal_of_an_unknown_catalogue_is_not_found(): void
    {
        $result = $this->service()->remove(EntityId::generate(), $this->staff);

        self::assertSame('Catalogues.NotFound', $result->getError()->code);
    }

    public function test_a_known_list_is_never_removed(): void
    {
        $known = Catalogue::factory()->ofType(SavedListType::KNOWNWORDS)->create(['user_id' => $this->catalogue->user_id]);

        $result = $this->service()->remove(new EntityId((string) $known->uuid), $this->staff);

        self::assertSame('Catalogues.AccessDenied', $result->getError()->code);
        self::assertNotNull($known->fresh());
        self::assertSame(0, AdminActivity::query()->count());
    }

    public function test_removal_deletes_and_writes_one_row_with_a_snapshot(): void
    {
        $result = $this->service()->remove($this->uuid(), $this->staff);

        self::assertTrue($result->isSuccess());
        self::assertNull($this->catalogue->fresh());
        $row = AdminActivity::query()->sole();
        self::assertSame(ActivityAction::CatalogueDeleted, $row->action);
        self::assertSame($this->catalogue->title, $row->changes['before']['title']);
        self::assertSame(0, $row->context['items']);
    }

    public function test_removal_rolls_back_when_the_activity_row_fails(): void
    {
        $this->app->instance(ActivityRecorderInterface::class, new FailingActivityRecorder);

        $result = $this->service()->remove($this->uuid(), $this->staff);

        self::assertSame('Catalogues.DeletionFailed', $result->getError()->code);
        $this->assertUnchangedWithoutActivity();
    }

    public function test_a_failed_delete_after_the_row_leaves_no_activity_row(): void
    {
        $this->failOn(DeleteCatalogueAction::class, 'execute');

        $result = $this->service()->remove($this->uuid(), $this->staff);

        self::assertSame('Catalogues.DeletionFailed', $result->getError()->code);
        $this->assertUnchangedWithoutActivity();
    }

    private function service(): CatalogueModerationServiceInterface
    {
        return app(CatalogueModerationServiceInterface::class);
    }

    private function uuid(): EntityId
    {
        return new EntityId((string) $this->catalogue->uuid);
    }

    private function assertUnchangedWithoutActivity(): void
    {
        $fresh = $this->catalogue->fresh();

        self::assertNotNull($fresh);
        self::assertTrue($fresh->publicity);
        self::assertSame(0, AdminActivity::query()->count());
    }
}
