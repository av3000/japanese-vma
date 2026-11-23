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

    public static function accessDenied(string $identifier): ResultError
    {
        return new ResultError(
            'POST_ACCESS_DENIED',
            HttpStatus::FORBIDDEN,
            "You do not have permission to modify post '{$identifier}'.",
        );
    }

    public static function creationFailed(): ResultError
    {
        return new ResultError(
            'POST_CREATION_FAILED',
            HttpStatus::INTERNAL_SERVER_ERROR,
            'Post creation failed.',
        );
    }

    public static function updateFailed(): ResultError
    {
        return new ResultError(
            'POST_UPDATE_FAILED',
            HttpStatus::INTERNAL_SERVER_ERROR,
            'Post update failed.',
        );
    }

    public static function deletionFailed(): ResultError
    {
        return new ResultError(
            'POST_DELETION_FAILED',
            HttpStatus::INTERNAL_SERVER_ERROR,
            'Post deletion failed.',
        );
    }

    public static function lockUpdateFailed(): ResultError
    {
        return new ResultError(
            'POST_LOCK_UPDATE_FAILED',
            HttpStatus::INTERNAL_SERVER_ERROR,
            'Post lock state could not be updated.',
        );
    }

    /**
     * Hashtag writes report their own failures (HashtagErrors); this wraps the
     * one case the Post slice adds - a tag rejected mid-transaction, where the
     * Post write must not be reported as a success.
     */
    public static function invalidTags(string $detail): ResultError
    {
        return new ResultError(
            'POST_INVALID_TAGS',
            HttpStatus::UNPROCESSABLE_ENTITY,
            'One or more hashtags were rejected.',
            $detail,
        );
    }
}
