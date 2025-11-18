<?php

namespace App\Shared\Enums;

enum HttpStatus: int
{
    case OK = 200;
    case CREATED = 201;
    case ACCEPTED = 202;
    case NO_CONTENT = 204;
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case CONFLICT = 409;
    case UNPROCESSABLE_ENTITY = 422;
    case INTERNAL_SERVER_ERROR = 500;

    public function isSuccess(): bool
    {
        return $this->value >= 200 && $this->value < 300;
    }

    public function isClientError(): bool
    {
        return $this->value >= 400 && $this->value < 500;
    }

    public function isServerError(): bool
    {
        return $this->value >= 500 && $this->value < 600;
    }

    public function getTypeUri(): string
    {
        return match ($this) {
            self::BAD_REQUEST => 'https://tools.ietf.org/html/rfc7231#section-6.5.1',
            self::UNAUTHORIZED => 'https://tools.ietf.org/html/rfc7231#section-6.5.2',
            self::FORBIDDEN => 'https://tools.ietf.org/html/rfc7231#section-6.5.3',
            self::NOT_FOUND => 'https://tools.ietf.org/html/rfc7231#section-6.5.4',
            self::CONFLICT => 'https://tools.ietf.org/html/rfc7231#section-6.5.8',
            self::UNPROCESSABLE_ENTITY => 'https://tools.ietf.org/html/rfc4918#section-11.2',
            self::INTERNAL_SERVER_ERROR => 'https://tools.ietf.org/html/rfc7231#section-6.6.1',
            default => 'about:blank',
        };
    }

    public function getHttpExceptionTitle(): string
    {
        return match ($this) {
            self::BAD_REQUEST => 'Bad Request',
            self::UNAUTHORIZED => 'Unauthorized',
            self::FORBIDDEN => 'Forbidden',
            self::NOT_FOUND => 'Not Found',
            self::CONFLICT => 'Conflict',
            self::UNPROCESSABLE_ENTITY => 'Validation Failed',
            self::INTERNAL_SERVER_ERROR => 'Internal Server Error',
            default => 'Error',
        };
    }
}
