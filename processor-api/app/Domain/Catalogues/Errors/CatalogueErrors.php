<?php

namespace App\Domain\Catalogues\Errors;

use App\Shared\Results\ResultError;
use App\Shared\Enums\HttpStatus;

class CatalogueErrors
{
    public static function notFound(string $catalogueUid): ResultError
    {
        return new ResultError(
            code: 'Catalogues.NotFound',
            status: HttpStatus::NOT_FOUND,
            description: 'Catalogue not found',
            detail: "Catalogue with ID {$catalogueUid} does not exist",
            errorMessage: "Catalogue with ID {$catalogueUid} does not exist",
        );
    }

    public static function accessDenied(string $catalogueUid): ResultError
    {
        return new ResultError(
            code: 'Catalogues.AccessDenied',
            status: HttpStatus::FORBIDDEN,
            description: 'Access denied',
            detail: "You don't have permission to access catalogue {$catalogueUid}",
            errorMessage: "You don't have permission to access catalogue {$catalogueUid}",
        );
    }

    public static function creationFailed(): ResultError
    {
        return new ResultError(
            code: 'Catalogues.CreationFailed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'Catalogue creation failed',
            detail: 'An unexpected error occurred during catalogue creation',
            errorMessage: 'An unexpected error occurred during catalogue creation',
        );
    }

    public static function updateFailed(string $errorMessage): ResultError
    {
        return new ResultError(
            code: 'Catalogues.UpdateFailed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'Catalogue update failed',
            detail: 'An unexpected error occurred during catalogue updating',
            errorMessage: $errorMessage,
        );
    }
}
