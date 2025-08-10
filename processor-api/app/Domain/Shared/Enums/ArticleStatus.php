<?php
namespace App\Domain\Shared\Enums;

enum ArticleStatus: int
{
    case PENDING = 0;
    case REVIEWING = 1;
    case REJECTED = 2;
    case APPROVED = 3;

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::REVIEWING => 'Under Review',
            self::REJECTED => 'Rejected',
            self::APPROVED => 'Approved',
        };
    }
}
