<?php

declare(strict_types=1);

namespace App\Infrastructure\Search;

use App\Application\Search\Interfaces\Caches\SearchCountCacheInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Throwable;

/**
 * Counts live in the default cache store. The keyword is hashed into the key, so any
 * text (spaces, punctuation, kanji) makes a valid key of fixed length.
 *
 * A cache that is down only costs a recount, so store errors are swallowed rather than
 * turned into a failed search.
 */
final readonly class CacheSearchCountCache implements SearchCountCacheInterface
{
    private const KEY_PREFIX = 'search-counts:v1';

    public function __construct(
        private CacheRepository $cache,
    ) {
    }

    public function get(string $scope, string $keyword): ?int
    {
        try {
            $stored = $this->cache->get($this->key($scope, $keyword));
        } catch (Throwable) {
            return null;
        }

        return is_int($stored) ? $stored : null;
    }

    public function put(string $scope, string $keyword, int $count, int $ttlSeconds): void
    {
        try {
            $this->cache->put($this->key($scope, $keyword), $count, $ttlSeconds);
        } catch (Throwable) {
            // The count was computed; failing to remember it must not fail the request.
        }
    }

    private function key(string $scope, string $keyword): string
    {
        return self::KEY_PREFIX.':'.$scope.':'.hash('sha256', $keyword);
    }
}
