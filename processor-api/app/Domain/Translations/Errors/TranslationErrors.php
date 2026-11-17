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

    /**
     * Edit side only: the locale exists but is switched off, so nothing may be saved against it.
     * Unlike `localeNotFound`, this is not served to the public, so it can say what is wrong.
     */
    public static function localeInactive(string $code): ResultError
    {
        return new ResultError(
            code: 'Translations.LocaleInactive',
            status: HttpStatus::UNPROCESSABLE_ENTITY,
            description: 'Locale is not active',
            detail: "Locale {$code} is not active",
            errorMessage: "Locale {$code} is not active",
        );
    }

    /** The key is not in the English catalogue (never was, or was removed). */
    public static function unknownKey(string $key): ResultError
    {
        return new ResultError(
            code: 'Translations.UnknownKey',
            status: HttpStatus::NOT_FOUND,
            description: 'Translation key not found',
            detail: "Key {$key} is not in the English catalogue",
            errorMessage: "Key {$key} is not in the English catalogue",
        );
    }

    /** English is the code-owned source: it has no overrides to save or to count. */
    public static function sourceLocaleNotEditable(): ResultError
    {
        return new ResultError(
            code: 'Translations.SourceLocaleNotEditable',
            status: HttpStatus::UNPROCESSABLE_ENTITY,
            description: 'English cannot be edited',
            detail: 'English is the source language and changes only through code review',
            errorMessage: 'English is the source language and changes only through code review',
        );
    }

    public static function valueTooLong(int $limit): ResultError
    {
        return new ResultError(
            code: 'Translations.ValueTooLong',
            status: HttpStatus::UNPROCESSABLE_ENTITY,
            description: 'Translation is too long',
            detail: "A translation may have at most {$limit} characters",
            errorMessage: "A translation may have at most {$limit} characters",
        );
    }
}
