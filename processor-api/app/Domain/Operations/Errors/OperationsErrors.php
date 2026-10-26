<?php

declare(strict_types=1);

namespace App\Domain\Operations\Errors;

use App\Shared\Enums\HttpStatus;
use App\Shared\Results\ResultError;

class OperationsErrors
{
    public static function accessDenied(): ResultError
    {
        return new ResultError(
            code: 'Operations.AccessDenied',
            status: HttpStatus::FORBIDDEN,
            description: 'Access denied',
            detail: "You don't have permission to manage operations",
            errorMessage: "You don't have permission to manage operations.",
        );
    }
}
