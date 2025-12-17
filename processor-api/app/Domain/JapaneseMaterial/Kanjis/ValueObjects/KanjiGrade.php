<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Kanjis\ValueObjects;

use InvalidArgumentException;

final readonly class KanjiGrade
{
    public const G1 = '1';
    public const G2 = '2';
    public const G3 = '3';
    public const G4 = '4';
    public const G5 = '5';
    public const G6 = '6';
    public const G7 = '7';
    public const G8 = '8';
    public const G9 = '9';
    public const UNASSIGNED = '-';

    public const VALID_GRADES = [
        self::G1,
        self::G2,
        self::G3,
        self::G4,
        self::G5,
        self::G6,
        self::G7,
        self::G8,
        self::G9,
        self::UNASSIGNED,
    ];

    public function __construct(public string $value)
    {
        if (!in_array($value, self::VALID_GRADES, true)) {
            throw new InvalidArgumentException("Invalid Kanji grade: {$value}. Must be one of " . implode(', ', self::VALID_GRADES));
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
