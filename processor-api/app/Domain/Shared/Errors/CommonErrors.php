<?php
namespace App\Domain\Shared\Errors;

use App\Shared\Enums\HttpStatus;
use App\Shared\Results\ResultError;

class CommonErrors
{
    public static function userNotFound(int $userId): ResultError
    {
        return new ResultError(
            code: 'Common.UserNotFound',
            status: HttpStatus::NOT_FOUND,
            description: 'User not found',
            detail: "User with ID {$userId} does not exist"
        );
    }

    public static function unauthorized(): ResultError
    {
        return new ResultError(
            code: 'Common.Unauthorized',
            status: HttpStatus::FORBIDDEN,
            description: 'Unauthorized access',
            detail: 'You are not authorized to perform this action'
        );
    }
}
