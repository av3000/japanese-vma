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
    public function createRole(string $name, ?string $guardName = null, array $permissions = []): Result;
    public function updateRole(string $currentName, string $newName, ?string $guardName = null, array $permissions = []): Result;
    public function syncRolePermissions(string $roleName, array $permissions): Result;
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

    /**
     * Permanently deletes a role.
     *
     * @param string $id
     * @return Result<string> Success: Id of deleted role, Failure: ResultError
     */
    public function deleteRole(string $id): Result;

    public function roleExists(string $roleName): bool;

    /**
     * @return array<string, array{label: string, permissions: array<string, string>}>
     */
    public function getGroupedAssignablePermissions(): array;
}
