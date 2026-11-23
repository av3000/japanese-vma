<?php

declare(strict_types=1);

namespace App\Application\Operations\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Operations\DTOs\EnvironmentHealthSnapshotDTO;
use App\Shared\Results\Result;

/**
 * What the admin panel's environment page needs. Host and driver names help an attacker, so every
 * view is recorded, and so is every worker check, which puts a real job on the production queue.
 */
interface EnvironmentHealthServiceInterface
{
    /** The panel waits synchronously; anything longer belongs in a queued check. */
    public const WORKER_CHECK_TIMEOUT_SECONDS = 10;

    /** Each dependency check's budget, shown on the page. */
    public const CHECK_BUDGET_MS = HealthCheckRunner::BUDGET_MS;

    /**
     * Runs the dependency checks and reads the last worker check and secret presence.
     */
    public function snapshot(): EnvironmentHealthSnapshotDTO;

    /**
     * Needs `operations.manage`. A queue or cache failure is `Operations.WorkerCheckFailed`.
     *
     * @return Result Success payload: QueueWorkerCheckDTO
     */
    public function runWorkerCheck(AuthenticatedUser $actor): Result;

    public function recordViewed(AuthenticatedUser $actor): void;
}
