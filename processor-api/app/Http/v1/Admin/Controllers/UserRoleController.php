<?php

declare(strict_types=1);

namespace App\Http\V1\Admin\Controllers;

use App\Application\Users\Services\RoleServiceInterface;
use App\Domain\Shared\ValueObjects\EntityId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Domain\Shared\Enums\UserRole;
use App\Shared\Http\TypedResults;
use App\Http\Controllers\Controller;
use App\Http\V1\Admin\Requests\AssignRoleRequest;
use App\Http\V1\Admin\Requests\CreateRoleRequest;
use App\Http\V1\Admin\Requests\RemoveRoleRequest;
use App\Http\V1\Admin\Resources\RoleResource;
use App\Shared\Enums\HttpStatus;

class UserRoleController extends Controller
{
    public function __construct(
        private readonly RoleServiceInterface $roleService
    ) {}

    /**
     * Assign a role to a user.
     *
     * @param AssignRoleRequest $request
     * @param string $userUuid
     * @return JsonResponse
     */
    public function assignUserRole(AssignRoleRequest $request, string $userUuid): JsonResponse
    {
        $userEntityId = new EntityId($userUuid);
        $roleName = $request->getRoleName();

        $result = $this->roleService->assignRole($userEntityId, $roleName);

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        /** @var string $affectedUserUuid */
        $affectedUserUuid = $result->getData();

        return TypedResults::ok(['message' => 'Role assigned successfully', 'userUuid' => $affectedUserUuid]);
    }

    /**
     * Remove a role from a user.
     *
     * @param RemoveRoleRequest $request
     * @param string $userUuid
     * @return JsonResponse
     */
    public function removeUserRole(RemoveRoleRequest $request, string $userUuid): JsonResponse
    {
        $userEntityId = new EntityId($userUuid);
        $roleName = $request->getRoleName();

        $result = $this->roleService->removeRole($userEntityId, $roleName);

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        /** @var string $affectedUserUuid */
        $affectedUserUuid = $result->getData();

        return TypedResults::ok(['message' => 'Role removed successfully', 'userUuid' => $affectedUserUuid]);
    }


    /**
     * Create a new role in the system.
     *
     * @param CreateRoleRequest $request
     * @return JsonResponse
     */
    public function createRole(CreateRoleRequest $request): JsonResponse
    {
        $name = $request->getName();
        $guardName = $request->getGuardName();

        $result = $this->roleService->createRole($name, $guardName);

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        /** @var DomainRole $newRole */
        $newRole = $result->getData();

        return TypedResults::ok(new RoleResource($newRole));
    }

    /**
     * Get all roles assigned to a specific user.
     *
     * @param string $userUuid
     * @return JsonResponse
     */
    public function getUserRoles(string $userUuid): JsonResponse
    {
        $userEntityId = new EntityId($userUuid);
        $roles = $this->roleService->getUserRoles($userEntityId);

        return TypedResults::ok(['roles' => RoleResource::collection($roles)]);
    }

    /**
     * Get all available roles in the system.
     *
     * @return JsonResponse
     */
    public function getAllRoles(): JsonResponse
    {
        $roles = $this->roleService->getAllRoles();

        return TypedResults::ok(['roles' => RoleResource::collection($roles)]);
    }
}
