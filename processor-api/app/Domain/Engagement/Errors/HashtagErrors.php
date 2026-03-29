<?php

namespace App\Domain\Engagement\Errors;

use App\Shared\Enums\HttpStatus;
use App\Shared\Results\ResultError;

class HashtagErrors
{
    public static function invalidTag(string $tag): ResultError
    {
        return new ResultError(
            code: 'Hashtags.InvalidTag',
            description: 'Invalid hashtag format',
            status: HttpStatus::NOT_FOUND,
            detail: "Tag '{$tag}' contains invalid characters or format"
        );
    }

    public static function tooManyTags(int $count, int $limit): ResultError
    {
        return new ResultError(
            code: 'Hashtags.TooManyTags',
            status: HttpStatus::BAD_REQUEST,
            description: 'Too many hashtags',
            detail: "Cannot add {$count} hashtags. Maximum allowed is {$limit}"
        );
    }

    public static function creationFailed(): ResultError
    {
        return new ResultError(
            code: 'Hashtags.CreationFailed',
            status: HttpStatus::BAD_REQUEST,
            description: 'Hashtag creation failed',
            detail: 'An unexpected error occurred while creating hashtags'
        );
    }
}
