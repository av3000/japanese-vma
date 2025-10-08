<?php
namespace App\Domain\Articles\ValueObjects;

use InvalidArgumentException;

readonly class ArticleSearchTerm
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

    public static function fromInput(?string $input): ?self
    {
        if($input === null)
        {
            return null;
        }

        $trimmed = trim($input);
        return new self($trimmed);
    }

    public static function fromInputOrNull(?string $input): ?self
    {
        return $input !== null ? self::fromInput($input) : null;
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
