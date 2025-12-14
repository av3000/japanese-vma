<?php

namespace App\Application\Users\Services;

use App\Domain\Users\Models\Role as DomainRole;
use App\Domain\Shared\Enums\UserRole;
use App\Domain\Shared\ValueObjects\{EntityId};
use App\Domain\Users\Queries\RoleQueryCriteria;
use App\Shared\Results\Result;

interface RoleServiceInterface
{
    public function userHasRole(EntityId $userUuid, string|UserRole $role): bool;
    public function isAdmin(EntityId $userUuid): bool;
    public function assignRole(EntityId $userUuid, string $roleName): Result;
    public function removeRole(EntityId $userUuid, string $roleName): Result;
    public function createRole(string $name, ?string $guardName = null): Result;
    /**
     * Find roles based on specified criteria.
     *
     * @param RoleQueryCriteria|null $criteria Optional criteria for filtering roles.
     * @return DomainRole[]
     */
    public function findRoles(?RoleQueryCriteria $criteria = null): array;
    /**
     * Get all available roles (convenience method).
     *
     * @return DomainRole[]
     */
    public function getAllRoles(): array;
    /**
     * Get all roles for a specific user (convenience method).
     *
     * @param EntityId $userUuid The public UUID of the user.
     * @return DomainRole[]
     */
    public function getUserRoles(EntityId $userUuid): array;

    public function roleExists(string $roleName): bool;
}
