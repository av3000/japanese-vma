<?php

declare(strict_types=1);

namespace App\Domain\Users\Errors;

use App\Shared\Enums\HttpStatus;
use App\Shared\Results\Error;

class RoleErrors
{
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
}
