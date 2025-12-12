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
            description: sprintf("Role '%s' is not a valid role type.", $roleName),
            errorMessage: sprintf("Role '%s' is invalid.", $roleName),
        );
    }
}
