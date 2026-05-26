<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Sentences\Errors;

use App\Shared\Enums\HttpStatus;
use App\Shared\Results\ResultError;

final readonly class SentenceErrors
{
    public static function notFound(string $identifier): ResultError
    {
        return new ResultError(
            'SENTENCE_NOT_FOUND',
            HttpStatus::NOT_FOUND,
            "Sentence with identifier '{$identifier}' not found.",
        );
    }

    public static function invalidIdentifier(): ResultError
    {
        return new ResultError(
            'INVALID_SENTENCE_IDENTIFIER',
            HttpStatus::BAD_REQUEST,
            'Identifier must be a valid UUID or numeric sentence ID.',
        );
    }
}
