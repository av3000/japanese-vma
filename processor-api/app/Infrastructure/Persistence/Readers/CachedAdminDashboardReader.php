<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Readers;

use App\Application\AdminDashboard\Interfaces\Caches\AdminDashboardCacheInterface;
use App\Application\AdminDashboard\Interfaces\Readers\AdminDashboardReaderInterface;
use App\Domain\AdminDashboard\DTOs\AttentionCountsDTO;
use App\Domain\AdminDashboard\DTOs\ContentStatsDTO;
use App\Domain\AdminDashboard\DTOs\ReviewQueueItemDTO;
use App\Domain\AdminDashboard\DTOs\WeeklyGrowthDTO;
use Closure;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Serves dashboard aggregates from the shared cache for five minutes. Keys are not per user, as
 * the figures are the same for every administrator. A cache outage (production cache is Upstash
 * Redis) falls back to the live read instead of breaking the dashboard.
 *
 * Values are cached as plain arrays so a DTO change between deploys cannot leave an
 * unserialisable entry behind.
 */
final readonly class CachedAdminDashboardReader implements AdminDashboardCacheInterface, AdminDashboardReaderInterface
{
    public const TTL_SECONDS = 300;

    public const KEY_PREFIX = 'admin-dashboard:v1:';

    private const KEYS = ['content-stats', 'attention-counts', 'weekly-growth', 'review-queue'];

    public function __construct(
        private AdminDashboardReaderInterface $inner,
        private CacheRepository $cache,
    ) {
    }

    public function contentStats(): ContentStatsDTO
    {
        return ContentStatsDTO::fromArray($this->remember(
            'content-stats',
            fn (): array => $this->inner->contentStats()->toArray(),
        ));
    }

    public function attentionCounts(): AttentionCountsDTO
    {
        return AttentionCountsDTO::fromArray($this->remember(
            'attention-counts',
            fn (): array => $this->inner->attentionCounts()->toArray(),
        ));
    }

    public function weeklyGrowth(): WeeklyGrowthDTO
    {
        return WeeklyGrowthDTO::fromArray($this->remember(
            'weekly-growth',
            fn (): array => $this->inner->weeklyGrowth()->toArray(),
        ));
    }

    public function reviewQueue(): array
    {
        $items = $this->remember(
            'review-queue',
            fn (): array => array_map(
                static fn (ReviewQueueItemDTO $item): array => $item->toArray(),
                $this->inner->reviewQueue(),
            ),
        );

        return array_map(ReviewQueueItemDTO::fromArray(...), $items);
    }

    public function forget(): void
    {
        foreach (self::KEYS as $key) {
            try {
                $this->cache->forget(self::KEY_PREFIX.$key);
            } catch (Throwable $exception) {
                Log::warning('Admin dashboard cache forget failed', ['key' => $key, 'error' => $exception->getMessage()]);
            }
        }
    }

    /**
     * @template T of array
     *
     * @param Closure(): T $read
     *
     * @return T
     */
    private function remember(string $key, Closure $read): array
    {
        try {
            return $this->cache->remember(self::KEY_PREFIX.$key, self::TTL_SECONDS, $read);
        } catch (Throwable $exception) {
            Log::warning('Admin dashboard cache unavailable, reading live', ['key' => $key, 'error' => $exception->getMessage()]);

            return $read();
        }
    }
}
