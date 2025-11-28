<?php

declare(strict_types=1);

namespace App\Application\Users\Services;

use App\Application\Users\Interfaces\Repositories\{UserRepositoryInterface, RoleRepositoryInterface};
use App\Domain\Shared\ValueObjects\{EntityId, UserId};
use App\Domain\Shared\Enums\UserRole;
use App\Domain\Users\DTOs\RoleDTO;

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


    public function assignRole(EntityId $userUuid, string|UserRole $role): void
    {
        $user = $this->userRepository->findByUuid($userUuid);

        if (!$user) {
            throw new \InvalidArgumentException("User not found: {$userUuid->value()}");
        }

        $roleName = $role instanceof UserRole ? $role->value : $role;

        $this->roleRepository->assignRole($user->getId(), $roleName);
    }

    public function removeRole(EntityId $userUuid, string|UserRole $role): void
    {
        $user = $this->userRepository->findByUuid($userUuid);

        if (!$user) {
            throw new \InvalidArgumentException("User not found: {$userUuid->value()}");
        }

        $roleName = $role instanceof UserRole ? $role->value : $role;

        $this->roleRepository->removeRole($user->getId(), $roleName);
    }

    /**
     * Get all roles for user
     *
     * @return RoleDTO[]
     */
    public function getUserRoles(EntityId $userUuid): array
    {
        $user = $this->userRepository->findByUuid($userUuid);

        return $user?->getRoles() ?? [];
    }
}
