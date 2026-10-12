<?php

declare(strict_types=1);

namespace App\Application\AdminDashboard\Interfaces\Caches;

/**
 * Invalidation port for the cached dashboard aggregates, used by the dashboard's refresh action.
 */
interface AdminDashboardCacheInterface
{
    public function forget(): void;
}
