<?php

namespace App\Domain\Catalogues\Errors;

use App\Shared\Results\Error;
use App\Shared\Enums\HttpStatus;

class CatalogueErrors
{
    public static function notFound(string $catalogueUid): Error
    {
        return new Error(
            code: 'Catalogues.NotFound',
            status: HttpStatus::NOT_FOUND,
            description: 'Catalogue not found',
            detail: "Catalogue with ID {$catalogueUid} does not exist",
            errorMessage: "Catalogue with ID {$catalogueUid} does not exist",
        );
    }

    public static function accessDenied(string $catalogueUid): Error
    {
        return new Error(
            code: 'Catalogues.AccessDenied',
            status: HttpStatus::FORBIDDEN,
            description: 'Access denied',
            detail: "You don't have permission to access catalogue {$catalogueUid}",
            errorMessage: "You don't have permission to access catalogue {$catalogueUid}",
        );
    }
}
