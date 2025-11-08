<?php
namespace App\Domain\Shared\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case TESTUSER = 'testuser';

    public function label(): string
    {
        return match($this) {
            self::ADMIN => 'Administrator',
            self::TESTUSER => 'Test User',
        };
    }
}
