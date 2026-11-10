<?php

declare(strict_types=1);

namespace App\Application\Search\Interfaces\Caches;

/**
 * Short-lived storage for one scope's count of one keyword.
 *
 * The keyword is used exactly as given: the dictionary lists match case-sensitively,
 * so `Water` and `water` are different entries.
 */
interface SearchCountCacheInterface
{
    public function get(string $scope, string $keyword): ?int;

    public function put(string $scope, string $keyword, int $count, int $ttlSeconds): void;
}
