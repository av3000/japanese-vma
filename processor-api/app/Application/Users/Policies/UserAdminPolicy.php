<?php

declare(strict_types=1);

namespace App\Application\Users\Policies;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Auth\Interfaces\Providers\ActorPermissionCheckerInterface;
use App\Application\Users\Support\PanelPermission;

/**
 * Account administration by staff. Only UserAdminService reads this. Creating and updating share a
 * permission today, as they do in the panel's UserPolicy.
 */
class UserAdminPolicy
{
    public function __construct(
        private readonly ActorPermissionCheckerInterface $permissions,
    ) {
    }

    public function canCreate(AuthenticatedUser $actor): bool
    {
        return $this->permissions->allows($actor, PanelPermission::UsersUpdate);
    }

    public function canUpdate(AuthenticatedUser $actor): bool
    {
        return $this->permissions->allows($actor, PanelPermission::UsersUpdate);
    }
}
