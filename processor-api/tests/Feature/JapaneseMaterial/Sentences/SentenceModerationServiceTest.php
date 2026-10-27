<?php

declare(strict_types=1);

namespace Tests\Feature\JapaneseMaterial\Sentences;

use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\JapaneseMaterial\Sentences\Interfaces\Repositories\SentenceRepositoryInterface;
use App\Application\JapaneseMaterial\Sentences\Services\SentenceModerationServiceInterface;
use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Auth\Providers\PassportCurrentUserProvider;
use App\Infrastructure\Persistence\Models\AdminActivity;
use App\Infrastructure\Persistence\Models\Sentence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Feature\Filament\Concerns\InteractsWithAdminPanel;
use Tests\Support\FailingActivityRecorder;
use Tests\Support\InjectsWriteFailures;
use Tests\TestCase;

/**
 * SentenceModerationService at its own seam: staff remove user-authored sentences only, and the
 * removal and its single activity row commit or roll back together.
 */
final class SentenceModerationServiceTest extends TestCase
{
    use InjectsWriteFailures;
    use InteractsWithAdminPanel;
    use RefreshDatabase;

    private AuthenticatedUser $staff;

    private AuthenticatedUser $member;

    private Sentence $sentence;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAdminPanel();
        $this->staff = PassportCurrentUserProvider::authenticatedUserFor($this->createAdmin());
        $this->member = PassportCurrentUserProvider::authenticatedUserFor($this->createPanelUser('Member'));
        $this->sentence = $this->sentence('私が書いた文です。', $this->createPanelUser('Writer')->id);
    }

    public function test_removal_is_refused_for_non_staff(): void
    {
        $result = $this->service()->remove($this->uuid(), $this->member);

        self::assertSame('SENTENCE_ACCESS_DENIED', $result->getError()->code);
        $this->assertUnchangedWithoutActivity();
    }

    public function test_removal_of_an_unknown_sentence_is_not_found(): void
    {
        $result = $this->service()->remove(EntityId::generate(), $this->staff);

        self::assertSame('SENTENCE_NOT_FOUND', $result->getError()->code);
    }

    public function test_an_imported_sentence_is_never_removed(): void
    {
        $imported = $this->sentence('辞書の文です。', null);

        $result = $this->service()->remove(new EntityId((string) $imported->uuid), $this->staff);

        self::assertSame('SENTENCE_IMMUTABLE', $result->getError()->code);
        self::assertNotNull($imported->fresh());
        self::assertSame(0, AdminActivity::query()->count());
    }

    public function test_removal_deletes_and_writes_one_row_with_a_snapshot(): void
    {
        $result = $this->service()->remove($this->uuid(), $this->staff);

        self::assertTrue($result->isSuccess());
        self::assertNull($this->sentence->fresh());
        $row = AdminActivity::query()->sole();
        self::assertSame(ActivityAction::SentenceDeleted, $row->action);
        self::assertSame($this->sentence->uuid, $row->subject_id);
        self::assertSame('私が書いた文です。', $row->changes['before']['content']);
    }

    public function test_removal_rolls_back_when_the_activity_row_fails(): void
    {
        $this->app->instance(ActivityRecorderInterface::class, new FailingActivityRecorder);

        $result = $this->service()->remove($this->uuid(), $this->staff);

        self::assertSame('SENTENCE_DELETION_FAILED', $result->getError()->code);
        $this->assertUnchangedWithoutActivity();
    }

    public function test_a_failed_delete_after_the_row_leaves_no_activity_row(): void
    {
        $this->failOn(SentenceRepositoryInterface::class, 'delete');

        $result = $this->service()->remove($this->uuid(), $this->staff);

        self::assertSame('SENTENCE_DELETION_FAILED', $result->getError()->code);
        $this->assertUnchangedWithoutActivity();
    }

    private function service(): SentenceModerationServiceInterface
    {
        return app(SentenceModerationServiceInterface::class);
    }

    private function uuid(): EntityId
    {
        return new EntityId((string) $this->sentence->uuid);
    }

    private function sentence(string $content, ?int $authorId): Sentence
    {
        return Sentence::query()->forceCreate([
            'uuid' => (string) Str::uuid(),
            'content' => $content,
            'user_id' => $authorId,
        ]);
    }

    private function assertUnchangedWithoutActivity(): void
    {
        self::assertNotNull($this->sentence->fresh());
        self::assertSame(0, AdminActivity::query()->count());
    }
}
