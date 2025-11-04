<?php
namespace App\Domain\Shared\ValueObjects;

use InvalidArgumentException;

readonly class UserId
{
    public function __construct(
        private int $value
    ) {
        if ($this->value <= 0) {
            throw new InvalidArgumentException('User ID must be a positive integer');
        }
    }

    public static function from(int $value): self
    {
        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(UserId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
