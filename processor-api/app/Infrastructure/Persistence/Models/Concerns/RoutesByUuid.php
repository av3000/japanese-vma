<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models\Concerns;

/**
 * URLs built from these models carry the public uuid, never the numeric id: the admin panel's
 * record pages resolve by uuid, and ids stay a support-lookup detail.
 */
trait RoutesByUuid
{
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
