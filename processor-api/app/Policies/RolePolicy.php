<?php

declare(strict_types=1);

namespace App\Policies;

use App\Application\Users\Support\PanelPermission;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPanelPermission(PanelPermission::RolesView);
    }

    public function view(User $user, Role $role): bool
    {
        return $user->hasPanelPermission(PanelPermission::RolesView);
    }

    public function create(User $user): bool
    {
        return $user->hasPanelPermission(PanelPermission::RolesCreate);
    }

    public function update(User $user, Role $role): bool
    {
        return $user->hasPanelPermission(PanelPermission::RolesUpdate);
    }

    /**
     * System roles and roles still assigned to users are never deletable, whoever asks.
     */
    public function delete(User $user, Role $role): bool
    {
        if (! $user->hasPanelPermission(PanelPermission::RolesDelete)) {
            return false;
        }

        if (in_array($role->name, UserRole::values(), true)) {
            return false;
        }

        return ! $role->users()->exists();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
