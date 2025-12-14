<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Users\Interfaces\Repositories\RoleRepositoryInterface;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Users\Models\Role as DomainRole;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Users\Queries\RoleQueryCriteria;
use App\Infrastructure\Persistence\Models\User as PersistenceUser;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Models\Role as SpatieRole;

final class RoleRepository implements RoleRepositoryInterface
{
    public function find(?RoleQueryCriteria $criteria = null): array
    {
        $query = SpatieRole::query();

        if ($criteria?->roleName !== null) {
            $query->where('name', $criteria->roleName);
        }

        if ($criteria?->guardName !== null) {
            $query->where('guard_name', $criteria->guardName);
        }

        if ($criteria?->userId !== null) {
            $query->whereHas('users', function ($q) use ($criteria) {
                $q->where('model_id', $criteria->userId->value());
            });
        }

        $spatieRoles = $query->get();

        return $spatieRoles->map(function (SpatieRole $spatieRole) {
            return DomainRole::fromSpatieRole($spatieRole);
        })->toArray();
    }

    public function findByName(string $name): ?DomainRole
    {
        try {
            $role = SpatieRole::findByName($name);
            return $role ? DomainRole::fromSpatieRole($role) : null;
        } catch (RoleDoesNotExist $e) {
            return null;
        }
    }

    public function userHasRole(EntityId $userUuid, string $roleName): bool
    {
        return PersistenceUser::where('uuid', $userUuid->value())
            ->first()?->hasRole($roleName) ?? false;
    }

    public function assignRole(UserId $userId, string $roleName): bool
    {
        $user = PersistenceUser::findOrFail($userId->value());

        $user->assignRole($roleName);

        return true;
    }

    public function removeRole(UserId $userId, string $roleName): bool
    {
        $user = PersistenceUser::findOrFail($userId->value());

        $user->removeRole($roleName);

        return true;
    }

    public function createRole(string $name, string $guardName): DomainRole
    {
        /** @var SpatieRole $spatieRole */
        $spatieRole = SpatieRole::create([
            'name' => $name,
            'guard_name' => $guardName,
        ]);
        return DomainRole::fromSpatieRole($spatieRole);
    }

    public function deleteRole(string $name): bool
    {
        $resp = SpatieRole::where('name', $name)->delete();
        return boolval($resp);
    }

    public function hasActiveAssignments(string $name): bool
    {
        /** @var SpatieRole|null $spatieRole */
        $spatieRole = SpatieRole::where('name', $name)->first();

        if (!$spatieRole) {
            return false;
        }

        return $spatieRole->users()->exists();
    }

    public function exists(string $roleName): bool
    {
        return SpatieRole::where('name', $roleName)->exists();
    }
}
