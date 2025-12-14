<?php

namespace App\Domain\Shared\Enums;

enum UserSortField: string
{
    case CREATED_AT = 'created_at';
    case NAME = 'name';
    case EMAIL = 'email';

    public function label(): string
    {
        return match ($this) {
            self::CREATED_AT => 'Created At',
            self::NAME => 'Name',
            self::EMAIL => 'Email',
        };
    }
}
