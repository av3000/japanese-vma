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

    /** The text has a NUL byte or is not valid UTF-8, which a PostgreSQL text column cannot store. */
    public static function invalidValue(): ResultError
    {
        return new ResultError(
            code: 'Translations.InvalidValue',
            status: HttpStatus::UNPROCESSABLE_ENTITY,
            description: 'Translation contains invalid characters',
            detail: 'A translation must be valid UTF-8 text without NUL characters',
            errorMessage: 'A translation must be valid UTF-8 text without NUL characters',
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

    public static function updateDenied(): ResultError
    {
        return new ResultError(
            code: 'Translations.UpdateDenied',
            status: HttpStatus::FORBIDDEN,
            description: 'Access denied',
            detail: "You don't have permission to edit translations",
            errorMessage: "You don't have permission to edit translations.",
        );
    }

    public static function manageLocalesDenied(): ResultError
    {
        return new ResultError(
            code: 'Translations.ManageLocalesDenied',
            status: HttpStatus::FORBIDDEN,
            description: 'Access denied',
            detail: "You don't have permission to manage languages",
            errorMessage: "You don't have permission to manage languages.",
        );
    }

    public static function invalidLocaleCode(): ResultError
    {
        return new ResultError(
            code: 'Translations.InvalidLocaleCode',
            status: HttpStatus::UNPROCESSABLE_ENTITY,
            description: 'Invalid language code',
            detail: 'A language code is two lowercase letters, such as da or sv',
            errorMessage: 'A language code is two lowercase letters, such as da or sv.',
        );
    }

    public static function localeCodeTaken(string $code): ResultError
    {
        return new ResultError(
            code: 'Translations.LocaleCodeTaken',
            status: HttpStatus::UNPROCESSABLE_ENTITY,
            description: 'Language code already exists',
            detail: "A language with code {$code} already exists",
            errorMessage: "A language with code {$code} already exists.",
        );
    }

    public static function invalidLocaleName(int $limit): ResultError
    {
        return new ResultError(
            code: 'Translations.InvalidLocaleName',
            status: HttpStatus::UNPROCESSABLE_ENTITY,
            description: 'Invalid language name',
            detail: "A language name is required and has at most {$limit} characters",
            errorMessage: "A language name is required and has at most {$limit} characters.",
        );
    }

    /** English is the source and the fallback: it stays active and keeps its code. */
    public static function sourceLocaleLocked(): ResultError
    {
        return new ResultError(
            code: 'Translations.SourceLocaleLocked',
            status: HttpStatus::UNPROCESSABLE_ENTITY,
            description: 'English cannot be deactivated',
            detail: 'English is the source and fallback language: it stays active and keeps its code',
            errorMessage: 'English is the source and fallback language: it stays active and keeps its code.',
        );
    }
}
