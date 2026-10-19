<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPanelPermission('roles.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->hasPanelPermission('roles.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPanelPermission('roles.create');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->hasPanelPermission('roles.update');
    }

    /**
     * System roles and roles still assigned to users are never deletable, whoever asks.
     */
    public function delete(User $user, Role $role): bool
    {
        if (! $user->hasPanelPermission('roles.delete')) {
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
