<?php
namespace App\Domain\Shared\ValueObjects;

use InvalidArgumentException;

readonly class ArticleTitle
{
    private const MIN_LENGTH = 2;
    private const MAX_LENGTH = 256;

    public function __construct(public string $value)
    {
        if (mb_strlen($this->value) < self::MIN_LENGTH) {
            throw new InvalidArgumentException('Title must not be empty');
        }

        if (mb_strlen($this->value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Title cannot exceed ' . self::MAX_LENGTH . ' characters');
        }
    }

    public static function fromString(?string $input): ?self
    {
        if ($input === null || trim($input) === '') {
            return null;
        }

        return new self(trim($input));
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
