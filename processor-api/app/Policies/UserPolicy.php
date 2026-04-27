<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    public function view(User $user, User $record): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    public function update(User $user, User $record): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    public function delete(User $user, User $record): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
