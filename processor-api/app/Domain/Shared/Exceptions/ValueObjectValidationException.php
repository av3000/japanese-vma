<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

use App\Shared\Enums\HttpStatus;
use InvalidArgumentException;
use Throwable;

class ValueObjectValidationException extends InvalidArgumentException
{
    private array $errors = [];

    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null, array $errors = [])
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public static function forField(string $field, string $message): self
    {
        return new self("Validation failed for {$field}: {$message}", HttpStatus::UNPROCESSABLE_ENTITY->value, null, [$field => [$message]]);
    }
}
