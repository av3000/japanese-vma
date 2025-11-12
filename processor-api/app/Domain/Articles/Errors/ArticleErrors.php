<?php
namespace App\Domain\Articles\Errors;

use App\Shared\Results\Error;
use App\Shared\Enums\ErrorType;

class ArticleErrors
{
    public static function notFound(string $id): Error
    {
        return new Error(
            code: 'Articles.NotFound',
            type: ErrorType::NOT_FOUND,
            description: 'Article not found',
            detail: "Article with ID {$id} does not exist"
        );
    }

    public static function creationFailed(): Error
    {
        return new Error(
            code: 'Articles.CreationFailed',
            type: ErrorType::UNEXPECTED,
            description: 'Article creation failed',
            detail: 'An unexpected error occurred during article creation'
        );
    }

    public static function accessDenied(string $id): Error
    {
        return new Error(
            code: 'Articles.AccessDenied',
            type: ErrorType::FORBIDDEN,
            description: 'Access denied',
            detail: "You do not have permission to access article ${id}"
        );
    }
}
