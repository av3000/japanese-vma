<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Words\Errors;

use App\Shared\Enums\HttpStatus;
use App\Shared\Results\ResultError;

final readonly class WordErrors
{
    public static function notFound(string $identifier): ResultError
    {
        return new ResultError(
            'WORD_NOT_FOUND',
            HttpStatus::NOT_FOUND,
            "Word with identifier '{$identifier}' not found.",
        );
    }

    public static function invalidIdentifier(): ResultError
    {
        return new ResultError(
            'INVALID_IDENTIFIER',
            HttpStatus::BAD_REQUEST,
            'Identifier must be a valid UUID or exact word surface.',
        );
    }
}
