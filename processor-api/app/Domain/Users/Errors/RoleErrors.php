<?php

declare(strict_types=1);

namespace App\Domain\Users\Errors;

use App\Shared\Enums\HttpStatus;
use App\Shared\Results\Error;

class RoleErrors
{

    public static function failed(): Error
    {
        return new Error(
            code: 'Roles.Failed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'Role action has failed',
            detail: "Role action has failed",
            errorMessage: "Role actions has unexpectedly failed",
        );
    }

    public static function notFound(string $name): Error
    {
        return new Error(
            code: 'Roles.NotFound',
            status: HttpStatus::NOT_FOUND,
            description: 'Role not found',
            detail: "Role with name '{$name}' does not exist",
            errorMessage: "Role with name '{$name}' does not exist",
        );
    }

    /**
     * @param string $roleName The name of the invalid role.
     * @return Error
     */
    public static function invalidRole(string $roleName): Error
    {
        return new Error(
            code: 'Roles.InvalidRole',
            status: HttpStatus::BAD_REQUEST,
            description: "Role '{$roleName}' is not a valid role type.",
            detail: "The provided role '{$roleName}' does not exist in the system.",
            errorMessage: "Role '{$roleName}' is invalid.",
        );
    }

    public static function cannotRemoveAdminFromAdmin(string $roleName): Error
    {
        return new Error(
            code: 'Roles.CannotRemoveAdminFromAdmin',
            status: HttpStatus::FORBIDDEN,
            description: "Cannot remove the '{$roleName}' role from an admin user.",
            detail: "The role '{$roleName}' cannot be removed from a user who is currently an administrator.",
            errorMessage: "Cannot remove admin role from an admin.",
        );
    }

    public static function protectedRoleCannotBeRemoved(string $roleName): Error
    {
        return new Error(
            code: 'Roles.ProtectedRoleCannotBeRemoved',
            status: HttpStatus::FORBIDDEN,
            description: "The '{$roleName}' role cannot be removed from any user.",
            detail: "The role '{$roleName}' is a system-protected role and cannot be removed.",
            errorMessage: "Cannot remove protected role.",
        );
    }

    public static function protectedRoleCannotBeDeleted(string $roleName): Error
    {
        return new Error(
            code: 'Roles.protectedRoleCannotBeDeleted',
            status: HttpStatus::FORBIDDEN,
            description: "The '{$roleName}' role cannot be deleted.",
            detail: "The role '{$roleName}' is a system-protected role and cannot be deleted.",
            errorMessage: "Cannot delete protected role.",
        );
    }

    public static function invalidGuardName(string $guardName): Error
    {
        return new Error(
            code: 'Roles.InvalidGuardName',
            status: HttpStatus::BAD_REQUEST,
            description: "Guard name '{$guardName}' is not a valid system guard.",
            detail: "The provided guard name '{$guardName}' is not configured in the system.",
            errorMessage: "Invalid guard name.",
        );
    }

    public static function roleHasActiveAssignments(string $roleName): Error // Reusing this
    {
        return new Error(
            code: 'Roles.RoleHasActiveAssignments',
            status: HttpStatus::CONFLICT,
            description: 'Role has active assignments',
            detail: "Role with name '{$roleName}' has active user assignments and cannot be deleted.",
            errorMessage: "Role has active assignments.",
        );
    }
}
