<?php

declare(strict_types=1);

namespace App\Application\Operations\Services;

use App\Application\Operations\Interfaces\Caches\QueueWorkerMarkerStoreInterface;
use App\Domain\Operations\DTOs\QueueWorkerCheckDTO;
use App\Jobs\QueueSmokeMarkerJob;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Dispatches QueueSmokeMarkerJob and waits for the marker it writes to the shared store.
 */
final class QueueWorkerVerifier implements QueueWorkerVerifierInterface
{
    public function __construct(
        private readonly QueueWorkerMarkerStoreInterface $markers,
    ) {
    }

    public function verify(
        string $queue = 'default',
        int $timeoutSeconds = 30,
        int $ttlSeconds = 120,
        int $pollMilliseconds = 1000,
        ?callable $onDispatched = null,
    ): QueueWorkerCheckDTO {
        $markerKey = 'queue_smoke:'.Str::uuid()->toString();
        $started = hrtime(true);

        $this->markers->forget($markerKey);
        QueueSmokeMarkerJob::dispatch($markerKey, max($timeoutSeconds, $ttlSeconds))->onQueue($queue);

        if ($onDispatched !== null) {
            $onDispatched($markerKey);
        }

        $deadline = $started + max(1, $timeoutSeconds) * 1_000_000_000;
        $succeeded = false;

        while (true) {
            if ($this->markers->has($markerKey)) {
                $succeeded = true;
                break;
            }

            if (hrtime(true) >= $deadline) {
                break;
            }

            usleep(max(50, $pollMilliseconds) * 1000);
        }

        $result = new QueueWorkerCheckDTO(
            succeeded: $succeeded,
            queue: $queue,
            waitedMs: (int) round((hrtime(true) - $started) / 1_000_000),
            checkedAt: CarbonImmutable::now()->toAtomString(),
        );

        $this->markers->putLastResult($result);

        return $result;
    }

    public function lastResult(): ?QueueWorkerCheckDTO
    {
        return $this->markers->lastResult();
    }
}
