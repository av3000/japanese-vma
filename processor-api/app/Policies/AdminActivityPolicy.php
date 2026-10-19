<?php

declare(strict_types=1);

namespace App\Policies;

use App\Infrastructure\Persistence\Models\AdminActivity;
use App\Infrastructure\Persistence\Models\User;

/**
 * The activity log is append-only: readable with `activity.view`, never writable from the panel.
 */
class AdminActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPanelPermission('activity.view');
    }

    public function view(User $user, AdminActivity $activity): bool
    {
        return $user->hasPanelPermission('activity.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AdminActivity $activity): bool
    {
        return false;
    }

    public function delete(User $user, AdminActivity $activity): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
