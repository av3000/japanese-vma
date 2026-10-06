<?php

declare(strict_types=1);

namespace App\Domain\ContentImport\Errors;

use App\Shared\Enums\HttpStatus;
use App\Shared\Results\ResultError;

class ContentImportErrors
{
    public static function unknownSource(string $key): ResultError
    {
        return new ResultError(
            code: 'ContentImport.UnknownSource',
            status: HttpStatus::NOT_FOUND,
            description: 'Unknown content source',
            detail: "No content source is registered with key {$key}",
            errorMessage: "No content source is registered with key {$key}",
        );
    }

    public static function sourceDisabled(string $key): ResultError
    {
        return new ResultError(
            code: 'ContentImport.SourceDisabled',
            status: HttpStatus::CONFLICT,
            description: 'Content source disabled',
            detail: "Content source {$key} is disabled",
            errorMessage: "Content source {$key} is disabled",
        );
    }

    public static function runInProgress(string $key): ResultError
    {
        return new ResultError(
            code: 'ContentImport.RunInProgress',
            status: HttpStatus::CONFLICT,
            description: 'Import run in progress',
            detail: "Another import run of {$key} is still in progress",
            errorMessage: "Another import run of {$key} is still in progress",
        );
    }

    public static function systemAuthorMissing(): ResultError
    {
        return new ResultError(
            code: 'ContentImport.SystemAuthorMissing',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'System author missing',
            detail: 'The content import system user has not been seeded',
            errorMessage: 'The content import system user has not been seeded',
        );
    }
}
