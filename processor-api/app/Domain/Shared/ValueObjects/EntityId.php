<?php

namespace App\Domain\Shared\ValueObjects;

use Illuminate\Support\Str;

readonly class EntityId
{
    public function __construct(
        private string $value
    ) {
        if (empty($this->value)) {
            throw new \InvalidArgumentException('Entity ID cannot be empty');
        }

        if (!Str::isUuid($this->value)) {
            throw new \InvalidArgumentException('Entity ID must be a valid UUID');
        }
    }

    /**
     * Create from user input (HTTP requests, forms, etc.)
     * This is your main factory method for external data
     */
    public static function from(string $value): self
    {
        return new self(trim($value));
    }

    public static function isValid(string $uuid): bool
    {
        return Str::isUuid($uuid);
    }

    public static function generate(): self
    {
        return new self(Str::uuid()->toString());
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(EntityId $other): bool
    {
        return $this->value === $other->value;
    }
}
