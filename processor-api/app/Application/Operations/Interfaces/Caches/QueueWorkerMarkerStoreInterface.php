<?php

declare(strict_types=1);

namespace App\Application\Operations\Interfaces\Caches;

use App\Domain\Operations\DTOs\QueueWorkerCheckDTO;

/**
 * The shared store a queue worker writes its smoke-job marker to, and where the last check's
 * outcome is kept for the environment page.
 */
interface QueueWorkerMarkerStoreInterface
{
    public function forget(string $markerKey): void;

    public function has(string $markerKey): bool;

    /**
     * Best effort: a store that cannot keep the result must not hide the check's outcome.
     */
    public function putLastResult(QueueWorkerCheckDTO $result): void;

    /**
     * Null when no check has run, or when the store cannot be read.
     */
    public function lastResult(): ?QueueWorkerCheckDTO;
}
