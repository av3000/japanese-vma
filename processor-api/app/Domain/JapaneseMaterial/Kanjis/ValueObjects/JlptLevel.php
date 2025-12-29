<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Kanjis\ValueObjects;

use InvalidArgumentException;

final readonly class JlptLevel
{
    public const N1 = '1';
    public const N2 = '2';
    public const N3 = '3';
    public const N4 = '4';
    public const N5 = '5';
    public const UNASSIGNED = '-';

    public const VALID_LEVELS = [
        self::N1,
        self::N2,
        self::N3,
        self::N4,
        self::N5,
        self::UNASSIGNED,
    ];

    public function __construct(public string $value)
    {
        if (!in_array($value, self::VALID_LEVELS, true)) {
            throw new InvalidArgumentException("Invalid JLPT level: {$value}. Must be one of " . implode(', ', self::VALID_LEVELS));
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
