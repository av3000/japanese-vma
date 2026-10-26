<?php

declare(strict_types=1);

namespace App\Application\Operations\Policies;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Auth\Interfaces\Providers\ActorPermissionCheckerInterface;

/**
 * Operations that act on production infrastructure, such as the queue worker check. Only
 * EnvironmentHealthService reads this.
 */
class OperationsPolicy
{
    public function __construct(
        private readonly ActorPermissionCheckerInterface $permissions,
    ) {
    }

    public function canManage(AuthenticatedUser $actor): bool
    {
        return $this->permissions->allows($actor, 'operations.manage');
    }
}
