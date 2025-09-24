<?php

readonly class UserName
{
    public function __construct(
        private string $value
    ) {
        $trimmed = trim($this->value);
        if (empty($trimmed)) {
            throw new InvalidArgumentException('User name cannot be empty');
        }
        if (mb_strlen($trimmed) > 255) {
            throw new InvalidArgumentException('User name cannot exceed 255 characters');
        }
        $this->value = $trimmed;
    }

    public static function from(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(UserName $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
