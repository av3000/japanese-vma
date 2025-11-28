<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Users\Interfaces\Repositories\RoleRepositoryInterface;
use App\Domain\Users\DTOs\RoleDTO;
use App\Domain\Shared\ValueObjects\UserId;
use App\Infrastructure\Persistence\Models\User as PersistenceUser;

final class RoleRepository implements RoleRepositoryInterface
{
    public function getRolesForUser(UserId $userId): array
    {
        $user = PersistenceUser::find($userId->value());

        if (!$user) {
            return [];
        }

        return $user->getRoleNames()
            ->map(fn(string $roleName) => RoleDTO::fromName($roleName))
            ->toArray();
    }

    public function userHasRole(UserId $userId, string $roleName): bool
    {
        $user = PersistenceUser::find($userId->value());

        if (!$user) {
            return false;
        }

        return $user->hasRole($roleName);
    }

    public function assignRole(UserId $userId, string $roleName): void
    {
        $user = PersistenceUser::findOrFail($userId->value());
        $user->assignRole($roleName);
    }

    public function removeRole(UserId $userId, string $roleName): void
    {
        $user = PersistenceUser::findOrFail($userId->value());
        $user->removeRole($roleName);
    }
}
