<?php
namespace App\Domain\Shared\ValueObjects;

use InvalidArgumentException;

// TODO: probably to delete as duplicate of Pagination
readonly class PerPageLimit
{
    private const MIN_PER_PAGE = 1;
    private const MAX_PER_PAGE = 50;
    private const DEFAULT_PER_PAGE = 4;

    public function __construct(public int $value)
    {
        if ($this->value < self::MIN_PER_PAGE) {
            throw new InvalidArgumentException(
                "Per page limit must be at least " . self::MIN_PER_PAGE
            );
        }

        if ($this->value > self::MAX_PER_PAGE) {
            throw new InvalidArgumentException(
                "Per page limit cannot exceed " . self::MAX_PER_PAGE
            );
        }
    }

    public static function fromInputOrDefault(?int $input): self
    {
        if ($input === null) {
            return self::default();
        }

        return new self($input);
    }

    public static function default(): self
    {
        return new self(self::DEFAULT_PER_PAGE);
    }
}
