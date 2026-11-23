<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations;

use App\Application\Operations\Interfaces\Caches\QueueWorkerMarkerStoreInterface;
use App\Domain\Operations\DTOs\QueueWorkerCheckDTO;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Throwable;

/**
 * Markers and the last result live in the default cache store, which the queue workers share
 * (Upstash Redis in production).
 */
final class CacheQueueWorkerMarkerStore implements QueueWorkerMarkerStoreInterface
{
    public const LAST_RESULT_CACHE_KEY = 'operations:worker-check:last';

    private const LAST_RESULT_TTL_SECONDS = 7 * 24 * 3600;

    public function __construct(
        private readonly CacheRepository $cache,
    ) {
    }

    public function forget(string $markerKey): void
    {
        $this->cache->forget($markerKey);
    }

    public function has(string $markerKey): bool
    {
        return $this->cache->has($markerKey);
    }

    public function putLastResult(QueueWorkerCheckDTO $result): void
    {
        try {
            $this->cache->put(self::LAST_RESULT_CACHE_KEY, $result->toArray(), self::LAST_RESULT_TTL_SECONDS);
        } catch (Throwable) {
            // The check itself already ran; failing to remember it must not hide the outcome.
        }
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
}
