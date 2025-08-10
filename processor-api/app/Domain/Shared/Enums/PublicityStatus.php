<?php
namespace App\Domain\Shared\Enums;

enum PublicityStatus: int
{
    case PRIVATE = 0;
    case PUBLIC = 1;

    public function label(): string
    {
        return match($this) {
            self::PRIVATE => 'Private',
            self::PUBLIC => 'Public',
        };
    }
}
