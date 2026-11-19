<?php

declare(strict_types=1);

namespace App\Application\Translations\Policies;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Auth\Interfaces\Providers\ActorPermissionCheckerInterface;
use App\Application\Users\Support\PanelPermission;

/**
 * Adding, activating and deactivating languages decides what learners can choose. Only
 * TranslationLocaleAdminService reads this.
 */
class TranslationLocaleManagementPolicy
{
    public function __construct(
        private readonly ActorPermissionCheckerInterface $permissions,
    ) {
    }

    public function canManage(AuthenticatedUser $actor): bool
    {
        return $this->permissions->allows($actor, PanelPermission::LocalesManage);
    }
}
