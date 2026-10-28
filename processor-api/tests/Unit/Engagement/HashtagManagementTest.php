<?php

declare(strict_types=1);

namespace Tests\Unit\Engagement;

use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Auth\Interfaces\Providers\ActorPermissionCheckerInterface;
use App\Application\Engagement\Actions\MergeHashtagsAction;
use App\Application\Engagement\Actions\RenameHashtagAction;
use App\Application\Engagement\Interfaces\Repositories\HashtagRepositoryInterface;
use App\Application\Engagement\Policies\HashtagManagementPolicy;
use App\Application\Users\Support\PanelPermission;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;
use App\Shared\Enums\HttpStatus;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

/**
 * Renaming and merging have no API route; the panel hides them without `hashtags.manage`. These
 * tests hold the application layer to the same permission, so a future caller cannot skip it.
 */
final class HashtagManagementTest extends TestCase
{
    public function test_merge_is_refused_without_hashtags_manage(): void
    {
        $hashtags = $this->createMock(HashtagRepositoryInterface::class);
        $hashtags->expects(self::never())->method('findTag');
        $hashtags->expects(self::never())->method('merge');

        $result = (new MergeHashtagsAction($hashtags, $this->recorderNeverCalled(), $this->policy(allows: false)))
            ->execute(1, 2, $this->actor());

        self::assertTrue($result->isFailure());
        self::assertSame('Hashtags.ManageAccessDenied', $result->getError()->code);
        self::assertSame(HttpStatus::FORBIDDEN, $result->getError()->toHttpStatus());
    }

    public function test_rename_is_refused_without_hashtags_manage(): void
    {
        $hashtags = $this->createMock(HashtagRepositoryInterface::class);
        $hashtags->expects(self::never())->method('findTag');
        $hashtags->expects(self::never())->method('rename');

        $result = (new RenameHashtagAction($hashtags, $this->recorderNeverCalled(), $this->policy(allows: false)))
            ->execute(1, '#grammar', $this->actor());

        self::assertTrue($result->isFailure());
        self::assertSame('Hashtags.ManageAccessDenied', $result->getError()->code);
    }

    public function test_a_permitted_non_admin_reaches_the_merge_rules(): void
    {
        $result = (new MergeHashtagsAction(
            $this->createMock(HashtagRepositoryInterface::class),
            $this->recorderNeverCalled(),
            $this->policy(allows: true),
        ))->execute(1, 1, $this->actor());

        self::assertSame('Hashtags.MergeIntoItself', $result->getError()->code);
    }

    public function test_a_failing_rename_is_a_logged_typed_failure(): void
    {
        Log::spy();
        $hashtags = $this->createMock(HashtagRepositoryInterface::class);
        $hashtags->method('findTag')->willThrowException(new RuntimeException('connection lost'));

        $result = (new RenameHashtagAction($hashtags, $this->recorderNeverCalled(), $this->policy(allows: true)))
            ->execute(1, '#grammar', $this->actor());

        self::assertSame('Hashtags.RenameFailed', $result->getError()->code);
        self::assertSame(HttpStatus::INTERNAL_SERVER_ERROR, $result->getError()->toHttpStatus());
        Log::shouldHaveReceived('error')->once();
    }

    private function policy(bool $allows): HashtagManagementPolicy
    {
        $checker = $this->createMock(ActorPermissionCheckerInterface::class);
        $checker->expects(self::once())
            ->method('allows')
            ->with(self::isInstanceOf(AuthenticatedUser::class), PanelPermission::HashtagsManage)
            ->willReturn($allows);

        return new HashtagManagementPolicy($checker);
    }

    private function recorderNeverCalled(): ActivityRecorderInterface
    {
        $recorder = $this->createMock(ActivityRecorderInterface::class);
        $recorder->expects(self::never())->method('record');

        return $recorder;
    }

    private function actor(): AuthenticatedUser
    {
        return new AuthenticatedUser(UserId::from(7), EntityId::generate(), UserName::from('Tagger'), isAdmin: false);
    }
}
