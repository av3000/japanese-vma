<?php

declare(strict_types=1);

namespace App\Domain\Translations\Errors;

use App\Shared\Enums\HttpStatus;
use App\Shared\Results\ResultError;

class TranslationErrors
{
    /**
     * Returned for a code that is unknown and for a locale that exists but is inactive: a
     * deactivated language must look the same as one that was never added.
     */
    public static function localeNotFound(string $code): ResultError
    {
        return new ResultError(
            code: 'Translations.LocaleNotFound',
            status: HttpStatus::NOT_FOUND,
            description: 'Locale not found',
            detail: "Locale {$code} does not exist or is not active",
            errorMessage: "Locale {$code} does not exist or is not active",
        );
    }
}
