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
use App\Http\V1\Admin\Resources\RoleResource;

class UserRoleController extends Controller
{
    public function __construct(
        private readonly RoleServiceInterface $roleService
    ) {}

    /**
     * Assign a role to a user.
     *
     * @param Request $request
     * @param string $userUuid
     * @return JsonResponse
     */
    public function assignUserRole(Request $request, string $userUuid): JsonResponse
    {
        $request->validate([
            'role' => ['required', 'string', Rule::in(array_column(UserRole::cases(), 'value'))],
        ]);

        $userEntityId = new EntityId($userUuid);
        $roleName = $request->input('role');

        $result = $this->roleService->assignRole($userEntityId, $roleName);

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return TypedResults::ok(['message' => 'Role assigned successfully']);
    }

    /**
     * Remove a role from a user.
     *
     * @param Request $request
     * @param string $userUuid
     * @return JsonResponse
     */
    public function removeUserRole(Request $request, string $userUuid): JsonResponse
    {
        $request->validate([
            'role' => ['required', 'string', Rule::in(array_column(UserRole::cases(), 'value'))],
        ]);

        $userEntityId = new EntityId($userUuid);
        $roleName = $request->input('role');

        $result = $this->roleService->removeRole($userEntityId, $roleName);

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return TypedResults::ok(['message' => 'Role removed successfully']);
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
