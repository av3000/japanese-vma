<?php

declare(strict_types=1);

namespace App\Application\Operations\Services;

use App\Application\Activity\ActivityEntries;
use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Operations\Policies\OperationsPolicy;
use App\Application\Operations\Support\SecretPresence;
use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Activity\Enums\ActivitySubjectType;
use App\Domain\Operations\DTOs\EnvironmentHealthSnapshotDTO;
use App\Domain\Operations\Errors\OperationsErrors;
use App\Shared\Results\Result;
use Illuminate\Support\Facades\Log;
use Throwable;

final class EnvironmentHealthService implements EnvironmentHealthServiceInterface
{
    public function __construct(
        private readonly HealthCheckRunner $checks,
        private readonly QueueWorkerVerifierInterface $verifier,
        private readonly ActivityRecorderInterface $activityRecorder,
        private readonly OperationsPolicy $policy,
    ) {
    }

    public function snapshot(): EnvironmentHealthSnapshotDTO
    {
        return new EnvironmentHealthSnapshotDTO(
            dependencies: $this->checks->runAll(),
            lastWorkerCheck: $this->verifier->lastResult(),
            secretPresence: SecretPresence::describe(SecretPresence::fromConfig()),
        );
    }

    public function runWorkerCheck(AuthenticatedUser $actor): Result
    {
        if (! $this->policy->canManage($actor)) {
            return Result::failure(OperationsErrors::accessDenied());
        }

        try {
            $result = $this->verifier->verify(
                timeoutSeconds: self::WORKER_CHECK_TIMEOUT_SECONDS,
                pollMilliseconds: 250,
            );
        } catch (Throwable $exception) {
            Log::error('Queue worker check failed', [
                'user_id' => $actor->id->value(),
                'error' => $exception->getMessage(),
            ]);

            return Result::failure(OperationsErrors::workerCheckFailed());
        }

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
