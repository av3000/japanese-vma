<?php
namespace App\Domain\Shared\Errors;

use App\Shared\Results\Error;

class CommonErrors
{
    public static function userNotFound(int $userId): Error
    {
        return new Error(
            code: 'Common.UserNotFound',
            description: 'User not found',
            detail: "User with ID {$userId} does not exist"
        );
    }

    public static function unauthorized(): Error
    {
        return new Error(
            code: 'Common.Unauthorized',
            description: 'Unauthorized access',
            detail: 'You are not authorized to perform this action'
        );
    }
}
