<?php
namespace App\Domain\Articles\ValueObjects;

use InvalidArgumentException;

readonly class ArticleContent
{
    private const MIN_LENGTH = 10;
    private const MAX_LENGTH = 2000;

    public function __construct(public string $value)
    {
        $trimmed = trim($this->value);

        if (mb_strlen($trimmed) < self::MIN_LENGTH) {
            throw new InvalidArgumentException('Content must be at least ' . self::MIN_LENGTH . ' characters');
        }

        if (mb_strlen($trimmed) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Content cannot exceed ' . self::MAX_LENGTH . ' characters');
        }
    }

    public static function fromString(?string $input): ?self
    {
        if ($input === null || trim($input) === '') {
            return null;
        }

        return new self(trim($input));
    }

    public function excerpt(int $length = 100): string
    {
        if (mb_strlen($this->value) <= $length) {
            return $this->value;
        }

        return mb_substr($this->value, 0, $length) . '...';
    }

    public function wordCount(): int
    {
        return str_word_count($this->value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
