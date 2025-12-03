<?php

declare(strict_types=1);

namespace App\Application\Users\Services;

use App\Application\Users\Interfaces\Repositories\{UserRepositoryInterface, RoleRepositoryInterface};
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\Enums\UserRole;
use App\Domain\Users\Models\Role as DomainRole;
use App\Shared\Results\Result;
use App\Domain\Users\Errors\UserErrors;
use App\Domain\Users\Queries\RoleQueryCriteria;

class RoleService implements RoleServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RoleRepositoryInterface $roleRepository
    ) {}

    public function userHasRole(EntityId $userUuid, string|UserRole $role): bool
    {
        $user = $this->userRepository->findByUuid($userUuid);

        if (!$user) {
            return false;
        }

        return $user->hasRole($role);
    }

    public function isAdmin(EntityId $userUuid): bool
    {
        return $this->userHasRole($userUuid, UserRole::ADMIN);
    }

    /**
     * Assign a role to a user.
     *
     * @param EntityId $userUuid
     * @param string|UserRole $role
     * @return Result Success: true, Failure: Error (e.g., User not found, Role not found)
     * @throws \Illuminate\Database\QueryException If a database error occurs in the repository
     * @throws \Exception For other unexpected technical issues
     */
    public function assignRole(EntityId $userUuid, string|UserRole $role): Result
    {
        $user = $this->userRepository->findByUuid($userUuid);

        if (!$user) {
            return Result::failure(UserErrors::notFound($userUuid->value()));
        }

        $roleName = $role instanceof UserRole ? $role->value : $role;

        $this->roleRepository->assignRole($user->getId(), $roleName);

        return Result::success(true);
    }

    /**
     * Remove a role from a user.
     *
     * @param EntityId $userUuid
     * @param string|UserRole $role
     * @return Result Success: true, Failure: Error (e.g., User not found, Role not assigned)
     * @throws \Illuminate\Database\QueryException If a database error occurs in the repository
     * @throws \Exception For other unexpected technical issues
     */
    public function removeRole(EntityId $userUuid, string|UserRole $role): Result
    {
        $user = $this->userRepository->findByUuid($userUuid);

        if (!$user) {
            return Result::failure(UserErrors::notFound($userUuid->value()));
        }

        $roleName = $role instanceof UserRole ? $role->value : $role;

        $this->roleRepository->removeRole($user->getId(), $roleName);

        return Result::success(true);
    }

    /**
     * Find roles based on specified criteria.
     * This method replaces both getRolesForUser and getRoles.
     *
     * @param RoleQueryCriteria|null $criteria Optional criteria for filtering roles.
     * @return DomainRole[]
     */
    public function findRoles(?RoleQueryCriteria $criteria = null): array
    {
        return $this->roleRepository->find($criteria);
    }

    /**
     * Get all roles for a specific user (convenience method).
     *
     * @param EntityId $userUuid The public UUID of the user.
     * @return DomainRole[]
     */
    public function getUserRoles(EntityId $userUuid): array
    {
        $userResult = $this->userRepository->findByUuid($userUuid);

        if (!$userResult) {
            return []; // Or throw an exception, or return a Result<DomainRole[]>
        }
        $userId = $userResult->getId();

        $criteria = RoleQueryCriteria::forUser($userId);
        return $this->findRoles($criteria);
    }

    /**
     * Get all available roles (convenience method).
     *
     * @return DomainRole[]
     */
    public function getAllRoles(): array
    {
        return $this->findRoles();
    }
}
