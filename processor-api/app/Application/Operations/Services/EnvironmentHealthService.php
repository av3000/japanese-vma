<?php

declare(strict_types=1);

namespace App\Application\Operations\Services;

use App\Application\Activity\ActivityEntries;
use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Operations\Policies\OperationsPolicy;
use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Activity\Enums\ActivitySubjectType;
use App\Domain\Operations\DTOs\HealthCheckResultDTO;
use App\Domain\Operations\DTOs\QueueWorkerCheckDTO;
use App\Domain\Operations\Errors\OperationsErrors;
use App\Shared\Results\Result;

/**
 * What the admin panel's environment page needs. Host and driver names help an attacker, so
 * every view is recorded, and so is every worker check, which puts a real job on the production
 * queue.
 */
final class EnvironmentHealthService
{
    /** The panel waits synchronously; anything longer belongs in a queued check. */
    public const WORKER_CHECK_TIMEOUT_SECONDS = 10;

    public function __construct(
        private readonly HealthCheckRunner $checks,
        private readonly QueueWorkerVerifier $verifier,
        private readonly ActivityRecorderInterface $activityRecorder,
        private readonly OperationsPolicy $policy,
    ) {
    }

    /**
     * @return list<HealthCheckResultDTO>
     */
    public function dependencies(): array
    {
        return $this->checks->runAll();
    }

    public function lastWorkerCheck(): ?QueueWorkerCheckDTO
    {
        return $this->verifier->lastResult();
    }

    /**
     * Needs `operations.manage` (OperationsPolicy), checked before anything is dispatched.
     *
     * @return Result Success payload: QueueWorkerCheckDTO
     */
    public function runWorkerCheck(AuthenticatedUser $actor): Result
    {
        if (! $this->policy->canManage($actor)) {
            return Result::failure(OperationsErrors::accessDenied());
        }

        $result = $this->verifier->verify(
            timeoutSeconds: self::WORKER_CHECK_TIMEOUT_SECONDS,
            pollMilliseconds: 250,
        );

        $this->activityRecorder->record(ActivityEntries::by(
            $actor,
            ActivityAction::WorkerCheckRun,
            ActivitySubjectType::Environment,
            (string) config('app.env'),
            'Queue worker',
            context: ['succeeded' => $result->succeeded, 'waited_ms' => $result->waitedMs, 'queue' => $result->queue],
        ));

        return Result::success($result);
    }

    public function recordViewed(AuthenticatedUser $actor): void
    {
        $this->activityRecorder->record(ActivityEntries::by(
            $actor,
            ActivityAction::EnvironmentViewed,
            ActivitySubjectType::Environment,
            (string) config('app.env'),
            'Environment and health',
        ));
    }
}
