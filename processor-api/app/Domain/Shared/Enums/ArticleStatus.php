<?php
namespace App\Domain\Shared\Enums;

enum ArticleStatus: int
{
    case PENDING = 0;
    case PROCESSED = 1;
    case REVIEWING = 2;
    case REJECTED = 3;
    case APPROVED = 4;

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::PROCESSED => 'Processed',
            self::REVIEWING => 'Under Review',
            self::REJECTED => 'Rejected',
            self::APPROVED => 'Approved',
        };
    }
}
