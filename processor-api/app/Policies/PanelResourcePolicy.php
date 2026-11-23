<?php

declare(strict_types=1);

namespace App\Policies;

use App\Application\Users\Support\PanelPermission;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * The shape every panel resource policy shares: a view permission, optional update and delete
 * permissions, and no create or bulk delete. A null permission means "never", so a resource that
 * is read-only or deletion-only says so by returning null.
 *
 * These policies decide what the panel shows. Staff writes are then checked again by the module's
 * moderation service (ARCH-admin-boundary).
 */
abstract class PanelResourcePolicy
{
    abstract protected function viewPermission(): PanelPermission;

    abstract protected function updatePermission(): ?PanelPermission;

    abstract protected function deletePermission(): ?PanelPermission;

    public function viewAny(User $user): bool
    {
        return $user->hasPanelPermission($this->viewPermission());
    }

    public function view(User $user, Model $record): bool
    {
        return $user->hasPanelPermission($this->viewPermission());
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Model $record): bool
    {
        return $this->holds($user, $this->updatePermission());
    }

    public function delete(User $user, Model $record): bool
    {
        return $this->holds($user, $this->deletePermission());
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    protected function holds(User $user, ?PanelPermission $permission): bool
    {
        return $permission !== null && $user->hasPanelPermission($permission);
    }
}
