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

    public static function accessDenied(string $identifier): ResultError
    {
        return new ResultError(
            'SENTENCE_ACCESS_DENIED',
            HttpStatus::FORBIDDEN,
            "You do not have permission to modify sentence '{$identifier}'.",
        );
    }

    public static function creationFailed(): ResultError
    {
        return new ResultError(
            'SENTENCE_CREATION_FAILED',
            HttpStatus::INTERNAL_SERVER_ERROR,
            'Sentence creation failed.',
        );
    }

    public static function updateFailed(): ResultError
    {
        return new ResultError(
            'SENTENCE_UPDATE_FAILED',
            HttpStatus::INTERNAL_SERVER_ERROR,
            'Sentence update failed.',
        );
    }

    public static function deletionFailed(): ResultError
    {
        return new ResultError(
            'SENTENCE_DELETION_FAILED',
            HttpStatus::INTERNAL_SERVER_ERROR,
            'Sentence deletion failed.',
        );
    }
}
