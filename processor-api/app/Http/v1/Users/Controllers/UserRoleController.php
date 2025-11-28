<?php

namespace App\Http\v1\Users\Controllers;

use App\Application\Users\Services\RoleServiceInterface;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rule;

class UserRoleController extends Controller
{
    public function __construct(
        private readonly RoleServiceInterface $roleService
    ) {}

    /**
     * POST /api/users/{uuid}/roles
     */
    public function assignRole(string $uuid, Request $request)
    {
        $request->validate([
            'role' => ['required', 'string', Rule::in(UserRole::values())]
        ]);

        $this->roleService->assignRole(
            new EntityId($uuid),
            UserRole::from($request->input('role'))
        );

        return response()->json(['message' => 'Role assigned successfully']);
    }

    /**
     * DELETE /api/users/{uuid}/roles/{role}
     */
    public function removeRole(string $uuid, string $role)
    {
        $this->roleService->removeRole(
            new EntityId($uuid),
            $role
        );

        return response()->json(['message' => 'Role removed successfully']);
    }

    /**
     * GET /api/users/{uuid}/roles
     */
    public function getUserRoles(string $uuid)
    {
        $roles = $this->roleService->getUserRoles(new EntityId($uuid));

        return response()->json([
            'roles' => array_map(fn($role) => $role->getName(), $roles)
        ]);
    }
}
