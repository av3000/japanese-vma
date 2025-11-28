<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Users\Interfaces\Repositories\RoleRepositoryInterface;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Users\DTOs\RoleDTO;
use App\Infrastructure\Persistence\Models\User;

class SpatieRoleRepository implements RoleRepositoryInterface
{
    public function getRolesForUser(UserId $userId): array
    {
        $user = User::where('id', $userId->value())->firstOrFail();

        return $user->roles->map(fn($role) => RoleDTO::fromName($role->name))->toArray();
    }

    public function userHasRole(UserId $userId, string $roleName): bool
    {
        $user = User::where('id', $userId->value())->firstOrFail();
        return $user->hasRole($roleName);
    }

    public function assignRole(UserId $userId, string $roleName): void
    {
        $user = User::where('id', $userId->value())->firstOrFail();
        $user->assignRole($roleName);
    }

    public function removeRole(UserId $userId, string $roleName): void
    {
        $user = User::where('id', $userId->value())->firstOrFail();
        $user->removeRole($roleName);
    }
}
