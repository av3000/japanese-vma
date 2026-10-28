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

    public static function workerCheckFailed(): ResultError
    {
        return new ResultError(
            code: 'Operations.WorkerCheckFailed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'Worker check failed',
            detail: 'The smoke job could not be dispatched or its marker could not be read',
            errorMessage: 'The worker check could not run: the queue or the cache is unreachable.',
        );
    }
}
