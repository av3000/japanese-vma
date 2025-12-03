<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Users\Interfaces\Repositories\RoleRepositoryInterface;
use App\Domain\Users\Models\Role as DomainRole;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Users\Queries\RoleQueryCriteria;
use App\Infrastructure\Persistence\Models\User as PersistenceUser;
use Spatie\Permission\Models\Role as SpatieRole;

final class RoleRepository implements RoleRepositoryInterface
{
    public function find(?RoleQueryCriteria $criteria = null): array
    {
        $query = SpatieRole::query(); // Start with a query builder for Spatie's Role model

        // Apply filters based on criteria
        if ($criteria?->roleName !== null) {
            $query->where('name', $criteria->roleName);
        }

        if ($criteria?->guardName !== null) {
            $query->where('guard_name', $criteria->guardName);
        }

        // Handle filtering by userId if provided. This is a special case
        // as Spatie stores user-role relationships in a pivot table.
        if ($criteria?->userId !== null) {
            // Join the model_has_roles pivot table
            $query->whereHas('users', function ($q) use ($criteria) {
                $q->where('model_id', $criteria->userId->value()); // Assuming userId holds the internal ID
            });
        }

        $spatieRoles = $query->get(); // Execute the query

        // Map Spatie Role models to your Domain\Users\Models\Role objects
        return $spatieRoles->map(function (SpatieRole $spatieRole) {
            return DomainRole::fromSpatieRole($spatieRole);
        })->toArray();
    }

    public function userHasRole(UserId $userId, string $roleName): bool
    {
        $user = PersistenceUser::find($userId->value());
        return $user?->hasRole($roleName) ?? false;
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
}
