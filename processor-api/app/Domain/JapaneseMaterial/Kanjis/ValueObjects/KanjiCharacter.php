<?php

declare(strict_types=1);

namespace App\Domain\JapaneseMaterial\Kanjis\ValueObjects;

use InvalidArgumentException;

final readonly class KanjiCharacter
{
    public function __construct(public string $value)
    {
        if (!self::isValid($value)) {
            throw new InvalidArgumentException('Invalid Kanji character: ' . $value);
        }
    }

    public static function isValid(string $value): bool
    {
        return preg_match('/^\p{Han}$/u', $value) === 1;
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
