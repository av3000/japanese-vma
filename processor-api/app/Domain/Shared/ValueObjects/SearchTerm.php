<?php
namespace App\Domain\Shared\ValueObjects;

use InvalidArgumentException;

readonly class SearchTerm
{
    private const MIN_LENGTH = 2;
    private const MAX_LENGTH = 255;

    public function __construct(public string $value)
    {
        if (strlen($this->value) < self::MIN_LENGTH) {
            throw new InvalidArgumentException('Search term must be at least ' . self::MIN_LENGTH . ' characters');
        }

        if (strlen($this->value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Search term cannot exceed ' . self::MAX_LENGTH . ' characters');
        }
    }

    public static function fromInput(string $input): self
    {
        $trimmed = trim($input);

        if (empty($trimmed)) {
            throw new InvalidArgumentException('Search term cannot be empty');
        }

        return new self($trimmed);
    }

    public function matches(string $text): bool
    {
        return str_contains(strtolower($text), strtolower($this->value));
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function isEmpty(): bool
    {
        return empty($this->value);
    }
}
