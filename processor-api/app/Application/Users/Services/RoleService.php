<?php

declare(strict_types=1);

namespace App\Application\Users\Services;

use App\Application\Activity\ActivityEntries;
use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Users\Interfaces\Repositories\RoleRepositoryInterface;
use App\Application\Users\Interfaces\Repositories\UserRepositoryInterface;
use App\Application\Users\Support\PermissionCatalog;
use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Activity\Enums\ActivitySubjectType;
use App\Domain\Shared\Enums\UserRole;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Users\Errors\RoleErrors;
use App\Domain\Users\Errors\UserErrors;
use App\Domain\Users\Models\Role as DomainRole;
use App\Domain\Users\Queries\RoleQueryCriteria;
use App\Shared\Results\Result;
use Illuminate\Support\Facades\DB;

class RoleService implements RoleServiceInterface
{
    private const DEFAULT_GUARD_NAME = 'api';

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly ActivityRecorderInterface $activityRecorder,
    ) {
    }

    /**
     * Checks if a user has a specific role.
     * Delegates directly to RoleRepository, passing the public UUID.
     *
     * @param EntityId $userUuid The public UUID of the user.
     * @param string|UserRole $role The role name or UserRole enum.
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
     * Assign a role to a user. Granting `admin` makes the user a panel superuser, so only an admin
     * actor may grant it, whatever permission let the caller reach this method.
     *
     * @return Result<string> Success: User UUID, Failure: ResultError
     */
    public function assignRole(EntityId $userUuid, string $roleName, AuthenticatedUser $actor): Result
    {
        $user = $this->userRepository->findByUuid($userUuid);

        if (! $user) {
            return Result::failure(UserErrors::notFound($userUuid->value()));
        }

        if (! $this->roleExists($roleName)) {
            return Result::failure(RoleErrors::invalidRole($roleName));
        }

        if ($roleName === UserRole::ADMIN->value && ! $actor->isAdmin) {
            return Result::failure(RoleErrors::adminRoleRequiresAdmin());
        }

        if ($this->roleRepository->userHasRole($userUuid, $roleName)) {
            return Result::failure(UserErrors::roleAlreadyAssigned($roleName));
        }

        DB::transaction(function () use ($user, $roleName, $actor): void {
            $this->roleRepository->assignRole($user->getId(), $roleName);
            $this->activityRecorder->record(ActivityEntries::by(
                $actor,
                ActivityAction::UserRoleAssigned,
                ActivitySubjectType::User,
                $user->getUuid()->value(),
                $user->getName()->value(),
                context: ['role' => $roleName],
            ));
        });

        return Result::success($userUuid->value());
    }

    /**
     * Remove a role from a user.
     *
     * @return Result<string> Success: User UUID, Failure: ResultError
     */
    public function removeRole(EntityId $userUuid, string $roleName, AuthenticatedUser $actor): Result
    {
        $user = $this->userRepository->findByUuid($userUuid);

        if (! $user) {
            return Result::failure(UserErrors::notFound($userUuid->value()));
        }

        if ($roleName === UserRole::COMMON->value) {
            return Result::failure(RoleErrors::protectedRoleCannotBeRemoved($roleName));
        }

        if ($roleName === UserRole::ADMIN->value) {
            if ($user->hasRole(UserRole::ADMIN)) {
                return Result::failure(RoleErrors::cannotRemoveAdminFromAdmin($roleName));
            }
        }

        if (! $this->roleExists($roleName)) {
            return Result::failure(RoleErrors::invalidRole($roleName));
        }

        if (! $this->roleRepository->userHasRole($userUuid, $roleName)) {
            return Result::failure(UserErrors::roleNotAssigned($roleName));
        }

        DB::transaction(function () use ($user, $roleName, $actor): void {
            $this->roleRepository->removeRole($user->getId(), $roleName);
            $this->activityRecorder->record(ActivityEntries::by(
                $actor,
                ActivityAction::UserRoleRemoved,
                ActivitySubjectType::User,
                $user->getUuid()->value(),
                $user->getName()->value(),
                context: ['role' => $roleName],
            ));
        });

        return Result::success($userUuid->value());
    }

    /**
     * Permanently deletes a role, with guardrails.
     *
     * @param string $name The name of the role to delete.
     *
     * @return Result<string> Success: Name of deleted role, Failure: ResultError
     */
    public function deleteRole(string $name, ?AuthenticatedUser $actor = null): Result
    {
        $role = $this->roleRepository->findByName($name);

        if (! $role) {
            return Result::failure(RoleErrors::notFound($name));
        }

        if ($role->getName() === UserRole::ADMIN->value || $role->getName() === UserRole::COMMON->value) {
            return Result::failure(RoleErrors::protectedRoleCannotBeDeleted($role->getName()));
        }

        if ($this->roleRepository->hasActiveAssignments($name)) {
            return Result::failure(RoleErrors::roleHasActiveAssignments($name));
        }

        $success = DB::transaction(function () use ($role, $name, $actor): bool {
            if (! $this->roleRepository->deleteRole($name)) {
                return false;
            }

            $this->activityRecorder->record(ActivityEntries::by(
                $actor,
                ActivityAction::RoleDeleted,
                ActivitySubjectType::Role,
                $name,
                $name,
                before: $this->snapshot($role),
            ));

            return true;
        });

        if (! $success) {
            return Result::failure(RoleErrors::failed());
        }

        return Result::success($name);
    }

    /**
     * Find roles based on specified criteria.
     * This method replaces both getRolesForUser and getRoles.
     *
     * @param RoleQueryCriteria|null $criteria Optional criteria for filtering roles.
     *
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
     *
     * @return DomainRole[]
     */
    public function getUserRoles(EntityId $userUuid): array
    {
        $userId = $this->userRepository->getIdByUuid($userUuid);

        if (! $userId) {
            return [];
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
     * @param string|null $guardName Defaults to 'api' if null.
     *
     * @return Result<DomainRole> Success: The newly created DomainRole, Failure: ResultError
     */
    public function createRole(string $name, ?string $guardName = null, array $permissions = [], ?AuthenticatedUser $actor = null): Result
    {
        $actualGuardName = $guardName ?? self::DEFAULT_GUARD_NAME;

        if ($this->roleRepository->exists($name)) {
            return Result::failure(UserErrors::nameAlreadyExists($name));
        }

        $configuredGuards = array_keys(config('auth.guards'));
        if (! in_array($actualGuardName, $configuredGuards)) {
            return Result::failure(RoleErrors::invalidGuardName($actualGuardName));
        }

        $invalidPermissions = $this->getInvalidPermissions($permissions);
        if ($invalidPermissions !== []) {
            return Result::failure(RoleErrors::invalidPermissions($invalidPermissions));
        }

        return DB::transaction(function () use ($name, $actualGuardName, $permissions, $actor): Result {
            $newDomainRole = $this->roleRepository->createRole($name, $actualGuardName);
            $result = $permissions === []
                ? Result::success($newDomainRole)
                : $this->syncRolePermissions($newDomainRole->getName(), $permissions);

            if ($result->isSuccess()) {
                $this->activityRecorder->record(ActivityEntries::by(
                    $actor,
                    ActivityAction::RoleCreated,
                    ActivitySubjectType::Role,
                    $name,
                    $name,
                    after: $this->snapshot($result->getData()),
                ));
            }

            return $result;
        });
    }

    public function updateRole(string $currentName, string $newName, ?string $guardName = null, array $permissions = [], ?AuthenticatedUser $actor = null): Result
    {
        $role = $this->roleRepository->findByName($currentName);

        if (! $role) {
            return Result::failure(RoleErrors::notFound($currentName));
        }

        $actualGuardName = $guardName ?? $role->getGuardName();

        if ($role->isSystemRole() && ($currentName !== $newName || $role->getGuardName() !== $actualGuardName)) {
            return Result::failure(RoleErrors::protectedRoleIdentityCannotBeChanged($currentName));
        }

        if ($currentName !== $newName && $this->roleRepository->exists($newName)) {
            return Result::failure(UserErrors::nameAlreadyExists($newName));
        }

        $configuredGuards = array_keys(config('auth.guards'));
        if (! in_array($actualGuardName, $configuredGuards, true)) {
            return Result::failure(RoleErrors::invalidGuardName($actualGuardName));
        }

        $invalidPermissions = $this->getInvalidPermissions($permissions);
        if ($invalidPermissions !== []) {
            return Result::failure(RoleErrors::invalidPermissions($invalidPermissions));
        }

        return DB::transaction(function () use ($role, $currentName, $newName, $actualGuardName, $permissions, $actor): Result {
            $updatedRole = $role;
            if ($currentName !== $newName || $role->getGuardName() !== $actualGuardName) {
                $updatedRole = $this->roleRepository->updateRole($currentName, $newName, $actualGuardName);
            }

            $result = $this->syncRolePermissions($updatedRole->getName(), $permissions);

            if ($result->isSuccess()) {
                $before = $this->snapshot($role);
                $after = $this->snapshot($result->getData());

                if ($before !== $after) {
                    $this->activityRecorder->record(ActivityEntries::by(
                        $actor,
                        ActivityAction::RoleUpdated,
                        ActivitySubjectType::Role,
                        $newName,
                        $newName,
                        before: $before,
                        after: $after,
                    ));
                }
            }

            return $result;
        });
    }

    /**
     * @return array{name: string, guard_name: string, permissions: list<string>}
     */
    private function snapshot(DomainRole $role): array
    {
        $permissions = array_values($role->getPermissions());
        sort($permissions);

        return [
            'name' => $role->getName(),
            'guard_name' => $role->getGuardName(),
            'permissions' => $permissions,
        ];
    }

    public function syncRolePermissions(string $roleName, array $permissions): Result
    {
        if (! $this->roleRepository->exists($roleName)) {
            return Result::failure(RoleErrors::notFound($roleName));
        }

        if ($roleName === UserRole::COMMON->value && $permissions !== []) {
            return Result::failure(RoleErrors::baseRoleCannotHoldPermissions($roleName));
        }

        $invalidPermissions = $this->getInvalidPermissions($permissions);
        if ($invalidPermissions !== []) {
            return Result::failure(RoleErrors::invalidPermissions($invalidPermissions));
        }

        return Result::success($this->roleRepository->syncPermissions($roleName, $permissions));
    }

    public function getGroupedAssignablePermissions(): array
    {
        $assignablePermissions = array_fill_keys($this->roleRepository->getAssignablePermissions(), true);
        $groups = [];

        foreach (PermissionCatalog::groups() as $groupKey => $group) {
            $groups[$groupKey] = [
                'label' => $group['label'],
                'permissions' => array_filter(
                    $group['permissions'],
                    static fn (string $permissionName): bool => isset($assignablePermissions[$permissionName]),
                    ARRAY_FILTER_USE_KEY,
                ),
            ];
        }

        return $groups;
    }

    /**
     * @param array<int, string> $permissions
     *
     * @return array<int, string>
     */
    private function getInvalidPermissions(array $permissions): array
    {
        $assignablePermissions = array_fill_keys($this->roleRepository->getAssignablePermissions(), true);

        return array_values(array_filter(
            $permissions,
            static fn (string $permission): bool => ! isset($assignablePermissions[$permission]),
        ));
    }
}
