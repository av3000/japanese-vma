<?php

namespace App\Domain\Engagement\Errors;

use App\Shared\Enums\HttpStatus;
use App\Shared\Results\Error;

class HashtagErrors
{
    public static function invalidTag(string $tag): Error
    {
        return new Error(
            code: 'Hashtags.InvalidTag',
            description: 'Invalid hashtag format',
            status: HttpStatus::NOT_FOUND,
            detail: "Tag '{$tag}' contains invalid characters or format"
        );
    }

    public static function tooManyTags(int $count, int $limit): Error
    {
        return new Error(
            code: 'Hashtags.TooManyTags',
            status: HttpStatus::BAD_REQUEST,
            description: 'Too many hashtags',
            detail: "Cannot add {$count} hashtags. Maximum allowed is {$limit}"
        );
    }

    public static function creationFailed(): Error
    {
        return new Error(
            code: 'Hashtags.CreationFailed',
            status: HttpStatus::BAD_REQUEST,
            description: 'Hashtag creation failed',
            detail: 'An unexpected error occurred while creating hashtags'
        );
    }
}
