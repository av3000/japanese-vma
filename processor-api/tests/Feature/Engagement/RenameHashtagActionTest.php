<?php

declare(strict_types=1);

namespace Tests\Feature\Engagement;

use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Engagement\Actions\RenameHashtagAction;
use App\Domain\Activity\Enums\ActivityAction;
use App\Infrastructure\Auth\Providers\PassportCurrentUserProvider;
use App\Infrastructure\Persistence\Models\AdminActivity;
use App\Infrastructure\Persistence\Models\Uniquehashtag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Filament\Concerns\InteractsWithAdminPanel;
use Tests\Support\FailingActivityRecorder;
use Tests\TestCase;

/**
 * RenameHashtagAction against the database, without the panel.
 */
final class RenameHashtagActionTest extends TestCase
{
    use InteractsWithAdminPanel;
    use RefreshDatabase;

    private AuthenticatedUser $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAdminPanel();
        $this->staff = PassportCurrentUserProvider::authenticatedUserFor($this->createAdmin());
    }

    public function test_rename_writes_one_row(): void
    {
        $tag = Uniquehashtag::create(['content' => '#grammer']);

        $result = app(RenameHashtagAction::class)->execute($tag->id, '#grammar', $this->staff);

        self::assertTrue($result->isSuccess());
        self::assertSame('#grammar', $tag->fresh()->content);
        $row = AdminActivity::query()->sole();
        self::assertSame(ActivityAction::HashtagRenamed, $row->action);
        self::assertSame((string) $tag->id, $row->subject_id);
        self::assertSame(['content' => '#grammer'], $row->changes['before']);
    }

    public function test_an_unknown_tag_is_not_found(): void
    {
        $result = app(RenameHashtagAction::class)->execute(999_999, '#grammar', $this->staff);

        self::assertSame('Hashtags.NotFound', $result->getError()->code);
    }

    public function test_a_name_that_collides_after_normalisation_is_refused(): void
    {
        Uniquehashtag::create(['content' => '#jlpt']);
        $tag = Uniquehashtag::create(['content' => '#n5']);

        $result = app(RenameHashtagAction::class)->execute($tag->id, '＃ＪＬＰＴ', $this->staff);

        self::assertSame('Hashtags.NameTaken', $result->getError()->code);
        self::assertSame('#n5', $tag->fresh()->content);
        self::assertSame(0, AdminActivity::query()->count());
    }

    public function test_rename_rolls_back_when_the_activity_row_fails(): void
    {
        $tag = Uniquehashtag::create(['content' => '#grammer']);
        $this->app->instance(ActivityRecorderInterface::class, new FailingActivityRecorder);

        $result = app(RenameHashtagAction::class)->execute($tag->id, '#grammar', $this->staff);

        self::assertSame('Hashtags.RenameFailed', $result->getError()->code);
        self::assertSame('#grammer', $tag->fresh()->content);
        self::assertSame(0, AdminActivity::query()->count());
    }
}
