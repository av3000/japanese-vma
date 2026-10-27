<?php

declare(strict_types=1);

namespace App\Application\Operations\Services;

use App\Domain\Operations\DTOs\QueueWorkerCheckDTO;

/**
 * Proves a live worker is consuming the queue. Shared by `app:verify-queue-worker` in the deploy
 * pipeline and the admin panel's worker check, so both mean the same thing by "the worker is up".
 */
interface QueueWorkerVerifierInterface
{
    /**
     * Dispatches a smoke job and waits up to $timeoutSeconds for its marker. Throws when the queue
     * or the marker store cannot be reached.
     *
     * @param callable(string $markerKey): void|null $onDispatched
     */
    public function verify(
        string $queue = 'default',
        int $timeoutSeconds = 30,
        int $ttlSeconds = 120,
        int $pollMilliseconds = 1000,
        ?callable $onDispatched = null,
    ): QueueWorkerCheckDTO;

    public function lastResult(): ?QueueWorkerCheckDTO;
}
