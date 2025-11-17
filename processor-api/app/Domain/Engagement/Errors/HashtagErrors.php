<?php
namespace App\Domain\Hashtags\Errors;

use App\Shared\Results\Error;
use App\Shared\Enums\ErrorType;

class HashtagErrors
{
    public static function invalidTag(string $tag): Error
    {
        return new Error(
            code: 'Hashtags.InvalidTag',
            type: ErrorType::VALIDATION,
            description: 'Invalid hashtag format',
            detail: "Tag '{$tag}' contains invalid characters or format"
        );
    }

    public static function tooManyTags(int $count, int $limit): Error
    {
        return new Error(
            code: 'Hashtags.TooManyTags',
            type: ErrorType::VALIDATION,
            description: 'Too many hashtags',
            detail: "Cannot add {$count} hashtags. Maximum allowed is {$limit}"
        );
    }

    public static function creationFailed(): Error
    {
        return new Error(
            code: 'Hashtags.CreationFailed',
            type: ErrorType::UNEXPECTED,
            description: 'Hashtag creation failed',
            detail: 'An unexpected error occurred while creating hashtags'
        );
    }
}
