<?php

declare(strict_types=1);

namespace App\Application\Users\Interfaces\Repositories;

use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Users\Models\Role as DomainRole;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Users\Queries\RoleQueryCriteria;

interface RoleRepositoryInterface
{

    /**
     * Find roles based on specified criteria.
     *
     * @param RoleQueryCriteria|null $criteria Optional criteria for filtering roles.
     * @return DomainRole[]
     */
    public function find(?RoleQueryCriteria $criteria = null): array;

    /**
     * Check if a user has a specific role.
     *
     * @param EntityId $EntityId The internal ID of the user.
     * @param string $roleName The name of the role to check.
     * @return bool
     */
    public function userHasRole(EntityId $userUuid, string $roleName): bool;

    /**
     * Assign a role to a user.
     *
     * @param UserId $userId The internal ID (e.g., primary key) of the user.
     * @param string $roleName The name of the role to assign.
     * @return bool True on success, false otherwise.
     */
    public function assignRole(UserId $userId, string $roleName): bool;

    /**
     * Remove a role from a user.
     *
     * @param UserId $userId The internal ID (e.g., primary key) of the user.
     * @param string $roleName The name of the role to remove.
     * @return bool True on success, false otherwise.
     */
    public function removeRole(UserId $userId, string $roleName): bool;

    /**
     * Checks if a role with the given name exists in the persistence layer.
     *
     * @param string $roleName
     * @return bool
     */
    public function exists(string $roleName): bool;
}
