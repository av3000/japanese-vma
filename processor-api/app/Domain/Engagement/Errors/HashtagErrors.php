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

    public static function notFound(int $hashtagId): ResultError
    {
        return new ResultError(
            code: 'Hashtags.NotFound',
            status: HttpStatus::NOT_FOUND,
            description: 'Hashtag not found',
            detail: "Hashtag {$hashtagId} does not exist",
            errorMessage: 'That hashtag no longer exists.',
        );
    }

    public static function nameTaken(string $existing): ResultError
    {
        return new ResultError(
            code: 'Hashtags.NameTaken',
            status: HttpStatus::CONFLICT,
            description: 'Hashtag name already in use',
            detail: "The name matches the existing tag '{$existing}' once case and character width are ignored",
            errorMessage: "That name matches the existing tag '{$existing}'. Merge into it instead.",
        );
    }

    public static function invalidName(string $reason): ResultError
    {
        return new ResultError(
            code: 'Hashtags.InvalidName',
            status: HttpStatus::UNPROCESSABLE_ENTITY,
            description: 'Invalid hashtag name',
            detail: $reason,
            errorMessage: $reason,
        );
    }

    public static function manageAccessDenied(): ResultError
    {
        return new ResultError(
            code: 'Hashtags.ManageAccessDenied',
            status: HttpStatus::FORBIDDEN,
            description: 'Access denied',
            detail: "You don't have permission to rename or merge hashtags",
            errorMessage: "You don't have permission to rename or merge hashtags.",
        );
    }

    public static function mergeIntoItself(): ResultError
    {
        return new ResultError(
            code: 'Hashtags.MergeIntoItself',
            status: HttpStatus::UNPROCESSABLE_ENTITY,
            description: 'A hashtag cannot be merged into itself',
            detail: 'Source and target are the same tag',
            errorMessage: 'Choose a different tag to merge into.',
        );
    }

    public static function mergeFailed(): ResultError
    {
        return new ResultError(
            code: 'Hashtags.MergeFailed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'Hashtag merge failed',
            detail: 'The merge was rolled back; both tags are unchanged',
            errorMessage: 'The merge failed and was rolled back. Both tags are unchanged.',
        );
    }

    public static function renameFailed(): ResultError
    {
        return new ResultError(
            code: 'Hashtags.RenameFailed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'Hashtag rename failed',
            detail: 'The rename was rolled back; the tag is unchanged',
            errorMessage: 'The rename failed and was rolled back. The tag is unchanged.',
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
