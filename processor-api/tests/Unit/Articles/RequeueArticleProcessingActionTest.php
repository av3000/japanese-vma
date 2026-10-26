<?php

declare(strict_types=1);

namespace Tests\Unit\Articles;

use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Application\Articles\Actions\Processing\RequeueArticleProcessingAction;
use App\Application\Articles\Interfaces\Readers\ArticleProcessingStateReaderInterface;
use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Application\Articles\Policies\ArticleProcessingPolicy;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Auth\Interfaces\Providers\ActorPermissionCheckerInterface;
use App\Application\Processing\Services\ProcessingStateServiceInterface;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;
use App\Shared\Enums\HttpStatus;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Requeueing puts a job on the production queue. The panel hides it without `operations.manage`;
 * the action checks the same permission so no other caller can skip it.
 */
final class RequeueArticleProcessingActionTest extends TestCase
{
    public function test_requeue_is_refused_without_operations_manage(): void
    {
        Queue::fake();

        $articles = $this->createMock(ArticleRepositoryInterface::class);
        $articles->expects(self::never())->method('findProcessingSource');
        $processingStates = $this->createMock(ProcessingStateServiceInterface::class);
        $processingStates->expects(self::never())->method('startOrReset');
        $recorder = $this->createMock(ActivityRecorderInterface::class);
        $recorder->expects(self::never())->method('record');

        $checker = $this->createMock(ActorPermissionCheckerInterface::class);
        $checker->expects(self::once())
            ->method('allows')
            ->with(self::isInstanceOf(AuthenticatedUser::class), 'operations.manage')
            ->willReturn(false);

        $result = (new RequeueArticleProcessingAction(
            $articles,
            $this->createMock(ArticleProcessingStateReaderInterface::class),
            $processingStates,
            $recorder,
            new ArticleProcessingPolicy($checker),
        ))->execute(EntityId::generate(), $this->actor(), force: true);

        self::assertTrue($result->isFailure());
        self::assertSame('Articles.ProcessingRequeueAccessDenied', $result->getError()->code);
        self::assertSame(HttpStatus::FORBIDDEN, $result->getError()->toHttpStatus());
        Queue::assertNothingPushed();
    }

    private function actor(): AuthenticatedUser
    {
        return new AuthenticatedUser(UserId::from(7), EntityId::generate(), UserName::from('Operator'), isAdmin: false);
    }
}
