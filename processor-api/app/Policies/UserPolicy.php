<?php

declare(strict_types=1);

namespace App\Policies;

use App\Infrastructure\Persistence\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPanelPermission('users.view');
    }

    public function view(User $user, User $record): bool
    {
        return $user->hasPanelPermission('users.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPanelPermission('users.update');
    }

    public function update(User $user, User $record): bool
    {
        return $user->hasPanelPermission('users.update');
    }

    /**
     * Granting or removing roles is separate from editing a profile, so `users.update` alone cannot
     * hand out `admin`. Granting `admin` itself additionally needs the actor to be an admin; see
     * assignAdminRole().
     */
    public function assignRoles(User $user, User $record): bool
    {
        return $user->hasPanelPermission('roles.assign');
    }

    public function assignAdminRole(User $user): bool
    {
        return $user->isPanelSuperuser();
    }

    /**
     * Users are never hard-deleted from the panel, including by admins.
     */
    public function delete(User $user, User $record): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
