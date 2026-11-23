<?php

declare(strict_types=1);

namespace App\Domain\Operations\DTOs;

/**
 * What the environment page shows on one view: the dependency checks, the last worker check, and
 * each configured secret as set or missing, never its value.
 */
final readonly class EnvironmentHealthSnapshotDTO
{
    /**
     * @param list<HealthCheckResultDTO> $dependencies
     * @param list<string> $secretPresence "Name: set" or "Name: missing"
     */
    public function __construct(
        public array $dependencies,
        public ?QueueWorkerCheckDTO $lastWorkerCheck,
        public array $secretPresence,
    ) {
    }
}
