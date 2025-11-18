<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

use InvalidArgumentException;

final readonly class Email
{
    public function __construct(
        private string $value
    ) {
        $trimmed = trim($this->value);

        if (empty($trimmed)) {
            throw new InvalidArgumentException('Email cannot be empty');
        }

        if (!filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }

        if (mb_strlen($trimmed) > 255) {
            throw new InvalidArgumentException('Email cannot exceed 255 characters');
        }
    }

    public static function from(string $value): self
    {
        return new self(trim($value));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
