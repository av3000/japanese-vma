<?php

declare(strict_types=1);

namespace App\Application\Operations\Services;

use App\Domain\Operations\DTOs\QueueWorkerCheckDTO;
use App\Jobs\QueueSmokeMarkerJob;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Str;
use Throwable;

/**
 * Proves a live worker is consuming the queue: dispatch QueueSmokeMarkerJob and wait for the cache
 * marker it writes. Used by `app:verify-queue-worker` in the deploy pipeline and by the admin
 * panel's Run worker check, so both mean the same thing by "the worker is up".
 */
class QueueWorkerVerifier
{
    public const LAST_RESULT_CACHE_KEY = 'operations:worker-check:last';

    public function __construct(
        private readonly CacheRepository $cache,
    ) {
    }

    /**
     * @param callable(string $cacheKey): void|null $onDispatched
     */
    public function verify(
        string $queue = 'default',
        int $timeoutSeconds = 30,
        int $ttlSeconds = 120,
        int $pollMilliseconds = 1000,
        ?callable $onDispatched = null,
    ): QueueWorkerCheckDTO {
        $cacheKey = 'queue_smoke:'.Str::uuid()->toString();
        $started = hrtime(true);

        $this->cache->forget($cacheKey);
        QueueSmokeMarkerJob::dispatch($cacheKey, max($timeoutSeconds, $ttlSeconds))->onQueue($queue);

        if ($onDispatched !== null) {
            $onDispatched($cacheKey);
        }

        $deadline = $started + max(1, $timeoutSeconds) * 1_000_000_000;
        $succeeded = false;

        while (true) {
            if ($this->cache->has($cacheKey)) {
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

        $this->remember($result);

        return $result;
    }

    public function lastResult(): ?QueueWorkerCheckDTO
    {
        try {
            $stored = $this->cache->get(self::LAST_RESULT_CACHE_KEY);
        } catch (Throwable) {
            return null;
        }

        return is_array($stored) ? QueueWorkerCheckDTO::fromArray($stored) : null;
    }

    private function remember(QueueWorkerCheckDTO $result): void
    {
        try {
            $this->cache->put(self::LAST_RESULT_CACHE_KEY, $result->toArray(), now()->addDays(7));
        } catch (Throwable) {
            // The check itself already ran; failing to remember it must not hide the outcome.
        }
    }
}
