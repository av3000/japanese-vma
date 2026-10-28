<?php

declare(strict_types=1);

namespace Tests\Unit\Operations;

use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Auth\Interfaces\Providers\ActorPermissionCheckerInterface;
use App\Application\Operations\Policies\OperationsPolicy;
use App\Application\Operations\Services\EnvironmentHealthService;
use App\Application\Operations\Services\HealthCheckRunner;
use App\Application\Operations\Services\QueueWorkerVerifierInterface;
use App\Application\Users\Support\PanelPermission;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;
use App\Shared\Enums\HttpStatus;
use Tests\TestCase;

/**
 * The worker check puts a real job on the production queue. The panel hides it without
 * `operations.manage`; the service checks the same permission before dispatching anything.
 */
final class EnvironmentHealthServiceTest extends TestCase
{
    public function test_worker_check_is_refused_without_operations_manage(): void
    {
        $verifier = $this->createMock(QueueWorkerVerifierInterface::class);
        $verifier->expects(self::never())->method('verify');
        $recorder = $this->createMock(ActivityRecorderInterface::class);
        $recorder->expects(self::never())->method('record');

        $checker = $this->createMock(ActorPermissionCheckerInterface::class);
        $checker->expects(self::once())
            ->method('allows')
            ->with(self::isInstanceOf(AuthenticatedUser::class), PanelPermission::OperationsManage)
            ->willReturn(false);

        $result = (new EnvironmentHealthService(new HealthCheckRunner([]), $verifier, $recorder, new OperationsPolicy($checker)))
            ->runWorkerCheck(new AuthenticatedUser(UserId::from(7), EntityId::generate(), UserName::from('Operator'), isAdmin: false));

        self::assertTrue($result->isFailure());
        self::assertSame('Operations.AccessDenied', $result->getError()->code);
        self::assertSame(HttpStatus::FORBIDDEN, $result->getError()->toHttpStatus());
    }
}
