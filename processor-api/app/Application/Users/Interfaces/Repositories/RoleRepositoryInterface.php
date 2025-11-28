<?php

declare(strict_types=1);

namespace App\Application\Users\Interfaces\Repositories;

use App\Domain\Users\DTOs\RoleDTO;
use App\Domain\Shared\ValueObjects\UserId;

interface RoleRepositoryInterface
{
    /**
     * Get all roles for a user
     *
     * @return RoleDTO[]
     */
    public function getRolesForUser(UserId $userId): array;

    /**
     * Check if user has specific role
     */
    public function userHasRole(UserId $userId, string $roleName): bool;

    /**
     * Assign role to user
     */
    public function assignRole(UserId $userId, string $roleName): void;

    /**
     * Remove role from user
     */
    public function removeRole(UserId $userId, string $roleName): void;
}
