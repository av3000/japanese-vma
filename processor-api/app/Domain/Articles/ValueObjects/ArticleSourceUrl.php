<?php
namespace App\Domain\Articles\ValueObjects;

use InvalidArgumentException;

readonly class ArticleSourceUrl
{
    private const MAX_LENGTH = 500;

    public function __construct(public string $value)
    {
        $trimmed = trim($this->value);

        if (mb_strlen($trimmed) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('URL cannot exceed ' . self::MAX_LENGTH . ' characters');
        }

        if (!filter_var($trimmed, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Invalid URL format');
        }
    }

    public static function fromString(?string $input): ?self
    {
        if ($input === null || trim($input) === '') {
            return null;
        }

        return new self(trim($input));
    }

    public function domain(): string
    {
        return parse_url($this->value, PHP_URL_HOST) ?? '';
    }

    public function isSecure(): bool
    {
        return strtolower(parse_url($this->value, PHP_URL_SCHEME) ?? '') === 'https';
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
