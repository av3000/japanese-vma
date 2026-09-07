<?php

declare(strict_types=1);

namespace App\Domain\Community\Posts\Errors;

use App\Shared\Enums\HttpStatus;
use App\Shared\Results\ResultError;

final readonly class PostErrors
{
    public static function notFound(string $identifier): ResultError
    {
        return new ResultError(
            'POST_NOT_FOUND',
            HttpStatus::NOT_FOUND,
            "Post with identifier '{$identifier}' not found.",
        );
    }

    public static function invalidIdentifier(): ResultError
    {
        return new ResultError(
            'INVALID_POST_IDENTIFIER',
            HttpStatus::BAD_REQUEST,
            'Identifier must be a valid UUID or numeric post ID.',
        );
    }
}
