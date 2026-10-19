<?php

declare(strict_types=1);

namespace App\Http\v1\Admin\Controllers;

use App\Application\Auth\Interfaces\Providers\CurrentUserProviderInterface;
use App\Application\Users\Services\RoleServiceInterface;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Http\Controllers\Controller;
use App\Http\v1\Admin\Requests\AssignRoleRequest;
use App\Http\v1\Admin\Requests\CreateRoleRequest;
use App\Http\v1\Admin\Requests\RemoveRoleRequest;
use App\Http\v1\Admin\Resources\RoleResource;
use App\Shared\Http\TypedResults;
use Illuminate\Http\JsonResponse;

class UserRoleController extends Controller
{
    public function __construct(
        private readonly RoleServiceInterface $roleService,
        private readonly CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

    /**
     * Assign a role to a user.
     */
    public function assignUserRole(AssignRoleRequest $request, string $userUuid): JsonResponse
    {
        $userEntityId = new EntityId($userUuid);
        $roleName = $request->getRoleName();

        $result = $this->roleService->assignRole($userEntityId, $roleName, $this->currentUserProvider->currentAuthenticatedUser());

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        /** @var string $affectedUserUuid */
        $affectedUserUuid = $result->getData();

        return TypedResults::ok(['message' => 'Role assigned successfully', 'userUuid' => $affectedUserUuid]);
    }

    /**
     * Remove a role from a user.
     */
    public function removeUserRole(RemoveRoleRequest $request, string $userUuid): JsonResponse
    {
        $userEntityId = new EntityId($userUuid);
        $roleName = $request->getRoleName();

        $result = $this->roleService->removeRole($userEntityId, $roleName, $this->currentUserProvider->currentAuthenticatedUser());

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        /** @var string $affectedUserUuid */
        $affectedUserUuid = $result->getData();

        return TypedResults::ok(['message' => 'Role removed successfully', 'userUuid' => $affectedUserUuid]);
    }

    /**
     * Create a new role in the system.
     */
    public function createRole(CreateRoleRequest $request): JsonResponse
    {
        $name = $request->getName();
        $guardName = $request->getGuardName();

        $result = $this->roleService->createRole($name, $guardName, actor: $this->currentUserProvider->currentAuthenticatedUser());

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        /** @var DomainRole $newRole */
        $newRole = $result->getData();

        return TypedResults::ok(new RoleResource($newRole));
    }

    /**
     * Permanently deletes a role.
     */
    public function deleteRole(string $name): JsonResponse
    {
        $result = $this->roleService->deleteRole($name, $this->currentUserProvider->currentAuthenticatedUser());

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        $affectedRoleName = $result->getData();

        return TypedResults::ok(['message' => 'Role deleted successfully', 'roleName' => $affectedRoleName]);
    }

    /**
     * Get all roles assigned to a specific user.
     */
    public function getUserRoles(string $userUuid): JsonResponse
    {
        $userEntityId = new EntityId($userUuid);
        $roles = $this->roleService->getUserRoles($userEntityId);

        return TypedResults::ok(['roles' => RoleResource::collection($roles)]);
    }

    /**
     * Get all available roles in the system.
     */
    public function getAllRoles(): JsonResponse
    {
        $roles = $this->roleService->getAllRoles();

        return TypedResults::ok(['roles' => RoleResource::collection($roles)]);
    }
}
