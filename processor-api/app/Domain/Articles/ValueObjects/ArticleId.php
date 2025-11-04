<?php
namespace App\Domain\Articles\ValueObjects;

use InvalidArgumentException;

readonly class ArticleId
{
    public function __construct(
        private int $value
    ) {
        if ($this->value <= 0) {
            throw new InvalidArgumentException('Article ID must be a positive integer');
        }
    }

    public static function from(int $value): self
    {
        return new self($value);
    }

    public static function generate(): self
    {
        // For new articles that haven't been persisted yet
        // In practice, this might generate a UUID or use other ID generation strategy
        return new self(0); // Temporary ID, will be set after persistence
    }

    public function value(): int
    {
        return $this->value;
    }

    public function isTemporary(): bool
    {
        return $this->value === 0;
    }

    public function equals(ArticleId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
