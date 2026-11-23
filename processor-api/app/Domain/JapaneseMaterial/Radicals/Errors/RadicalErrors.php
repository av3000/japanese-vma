<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Radicals\Errors;

use App\Shared\Enums\HttpStatus;
use App\Shared\Results\ResultError;

final readonly class RadicalErrors
{
    public static function notFound(string $identifier): ResultError
    {
        return new ResultError(
            'RADICAL_NOT_FOUND',
            HttpStatus::NOT_FOUND,
            "Radical with identifier '{$identifier}' not found.",
        );
    }

    public static function invalidIdentifier(): ResultError
    {
        return new ResultError(
            'INVALID_RADICAL_IDENTIFIER',
            HttpStatus::BAD_REQUEST,
            'Identifier must be a valid UUID or numeric radical ID.',
        );
    }
}
