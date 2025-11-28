<?php

namespace App\Domain\Shared\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case COMMON = 'common';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::COMMON => 'Common',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    public static function fromString(string $value): self
    {
        return self::from($value); // Throws if invalid
    }
}
