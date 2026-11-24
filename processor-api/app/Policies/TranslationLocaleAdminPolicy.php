<?php

declare(strict_types=1);

namespace App\Policies;

use App\Application\Users\Support\PanelPermission;
use App\Infrastructure\Persistence\Models\User;

/**
 * Language management is panel-only, so `locales.manage` is the whole rule. A language is switched
 * off, never deleted, so there is no delete permission.
 */
class TranslationLocaleAdminPolicy extends PanelResourcePolicy
{
    protected function viewPermission(): PanelPermission
    {
        return PanelPermission::LocalesManage;
    }

    protected function updatePermission(): ?PanelPermission
    {
        return PanelPermission::LocalesManage;
    }

    protected function deletePermission(): ?PanelPermission
    {
        return null;
    }

    public function create(User $user): bool
    {
        return $this->holds($user, PanelPermission::LocalesManage);
    }
}
