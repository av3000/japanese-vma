<?php

declare(strict_types=1);

namespace App\Application\Users\Services;

use App\Application\Users\Interfaces\Repositories\{UserRepositoryInterface, RoleRepositoryInterface};
use App\Application\Users\Policies\UserViewPolicy;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\Enums\UserRole;
use App\Domain\Users\Errors\RoleErrors;
use App\Domain\Users\Models\Role as DomainRole;
use App\Shared\Results\Result;
use App\Domain\Users\Errors\UserErrors;
use App\Domain\Users\Queries\RoleQueryCriteria;

class RoleService implements RoleServiceInterface
{
    private const DEFAULT_GUARD_NAME = 'api';

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly UserViewPolicy $userViewPolicy
    ) {}

    /**
     * Checks if a user has a specific role.
     * Delegates directly to RoleRepository, passing the public UUID.
     *
     * @param EntityId $userUuid The public UUID of the user.
     * @param string|UserRole $role The role name or UserRole enum.
     * @return bool
     */
    public function userHasRole(EntityId $userUuid, string|UserRole $role): bool
    {
        // TODO: probably one way should be expected and accepted rather than both
        $roleName = $role instanceof UserRole ? $role->value : $role;
        return $this->roleRepository->userHasRole($userUuid, $roleName);
    }

    public function isAdmin(EntityId $userUuid): bool
    {
        return $this->userHasRole($userUuid, UserRole::ADMIN);
    }

    /**
     * Assign a role to a user.
     *
     * @param EntityId $userUuid
     * @param string $roleName
     * @return Result<string> Success: User UUID, Failure: Error
     */
    public function assignRole(EntityId $userUuid, string $roleName): Result
    {
        $user = $this->userRepository->findByUuid($userUuid);

        if (!$user) {
            return Result::failure(UserErrors::notFound($userUuid->value()));
        }

        if (!$this->roleExists($roleName)) {
            return Result::failure(RoleErrors::invalidRole($roleName));
        }

        if ($this->roleRepository->userHasRole($userUuid, $roleName)) {
            return Result::failure(UserErrors::roleAlreadyAssigned($roleName));
        }

        $this->roleRepository->assignRole($user->getId(), $roleName);

        return Result::success($userUuid->value());
    }

    /**
     * Remove a role from a user.
     *
     * @param EntityId $userUuid
     * @param string $roleName
     * @return Result<string> Success: User UUID, Failure: Error
     */
    public function removeRole(EntityId $userUuid, string $roleName): Result
    {
        $user = $this->userRepository->findByUuid($userUuid);

        if (!$user) {
            return Result::failure(UserErrors::notFound($userUuid->value()));
        }

        if ($roleName === UserRole::COMMON) {
            return Result::failure(RoleErrors::protectedRoleCannotBeRemoved($roleName));
        }

        if ($roleName === UserRole::ADMIN->value) {
            if ($user->hasRole(UserRole::ADMIN)) {
                return Result::failure(RoleErrors::cannotRemoveAdminFromAdmin($roleName));
            }
        }

        if (!$this->roleExists($roleName)) {
            return Result::failure(RoleErrors::invalidRole($roleName));
        }

        if (!$this->roleRepository->userHasRole($userUuid, $roleName)) {
            return Result::failure(UserErrors::roleNotAssigned($roleName));
        }

        $this->roleRepository->removeRole($user->getId(), $roleName);

        // Return the UUID of the affected user
        return Result::success($userUuid->value());
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
        $userId = $this->userRepository->getIdByUuid($userUuid);

        if (!$userId) {
            return []; // Or throw an exception, or return a Result<DomainRole[]>
        }

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

    public function roleExists(string $roleName): bool
    {
        return $this->roleRepository->exists($roleName);
    }

    /**
     * Creates a new role.
     *
     * @param string $name
     * @param string|null $guardName Defaults to 'api' if null.
     * @return Result<DomainRole> Success: The newly created DomainRole, Failure: Error
     */
    public function createRole(string $name, ?string $guardName = null): Result
    {
        $actualGuardName = $guardName ?? self::DEFAULT_GUARD_NAME;

        if ($this->roleRepository->exists($name)) {
            return Result::failure(UserErrors::nameAlreadyExists($name));
        }

        $configuredGuards = array_keys(config('auth.guards'));
        if (!in_array($actualGuardName, $configuredGuards)) {
            return Result::failure(RoleErrors::invalidGuardName($actualGuardName));
        }

        $newDomainRole = $this->roleRepository->createRole($name, $actualGuardName); // Call repository
        return Result::success($newDomainRole);
    }
}
