<?php

declare(strict_types=1);

namespace App\Domain\Users\Errors;

use App\Shared\Results\Error;
use App\Shared\Enums\HttpStatus;

class UserErrors
{
    public static function notFound(): Error
    {
        return new Error(
            code: 'USER_NOT_FOUND',
            status: HttpStatus::NOT_FOUND,
            description: 'User not found',
            detail: 'The requested user does not exist'
        );
    }

    public static function unauthorized(): Error
    {
        return new Error(
            code: 'USER_UNAUTHORIZED',
            status: HttpStatus::FORBIDDEN,
            description: 'Unauthorized access',
            detail: 'You do not have permission to view this user profile'
        );
    }
}
