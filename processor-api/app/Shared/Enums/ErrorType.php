<?php
namespace App\Shared\Enums;

enum ErrorType: string
{
    case FAILURE = 'failure';
    case UNEXPECTED = 'unexpected';
    case VALIDATION = 'validation';
    case CONFLICT = 'conflict';
    case NOT_FOUND = 'not_found';
    case UNAUTHORIZED = 'unauthorized';
    case FORBIDDEN = 'forbidden';

    public function toHttpStatus(): int
    {
        return match($this) {
            self::VALIDATION => 422,
            self::CONFLICT => 409,
            self::NOT_FOUND => 404,
            self::UNAUTHORIZED => 401,
            self::FORBIDDEN => 403,
            self::FAILURE, self::UNEXPECTED => 500,
        };
    }
}
