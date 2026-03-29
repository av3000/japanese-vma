<?php

namespace App\Domain\Articles\Errors;

use App\Shared\Results\ResultError;
use App\Shared\Enums\HttpStatus;

class ArticleErrors
{
    public static function notFound(string $articleUid): ResultError
    {
        return new ResultError(
            code: 'Articles.NotFound',
            status: HttpStatus::NOT_FOUND,
            description: 'Article not found',
            detail: "Article with ID {$articleUid} does not exist",
            errorMessage: "Article with ID {$articleUid} does not exist",
        );
    }

    public static function accessDenied(string $articleUid): ResultError
    {
        return new ResultError(
            code: 'Articles.AccessDenied',
            status: HttpStatus::FORBIDDEN,
            description: 'Access denied',
            detail: "You don't have permission to access article {$articleUid}",
            errorMessage: "You don't have permission to access article {$articleUid}",
        );
    }

    public static function updateFailed(string $errorMessage): ResultError
    {
        return new ResultError(
            code: 'Articles.UpdateFailed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'Article update failed',
            detail: 'An unexpected error occurred during article updating',
            errorMessage: $errorMessage
        );
    }

    public static function creationFailed(): ResultError
    {
        return new ResultError(
            code: 'Articles.CreationFailed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'Article creation failed',
            detail: 'An unexpected error occurred during article creation',
            errorMessage: 'An unexpected error occurred during article creation',
        );
    }

    public static function deletionFailed(): ResultError
    {
        return new ResultError(
            code: 'Articles.DeletionFailed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'Article deletion failed',
            detail: 'An unexpected error occurred during article deletion',
            errorMessage: 'An unexpected error occurred during article deletion',
        );
    }

    public static function validationFailed(array $errors): ResultError
    {
        return new ResultError(
            code: 'Articles.ValidationFailed',
            status: HttpStatus::UNPROCESSABLE_ENTITY,
            description: 'Validation failed',
            detail: 'The provided data is invalid',
            errorMessage: json_encode($errors)
        );
    }
}
