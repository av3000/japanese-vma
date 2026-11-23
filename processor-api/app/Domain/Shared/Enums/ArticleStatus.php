<?php

namespace App\Domain\Shared\Enums;

enum ArticleStatus: int
{
    case PENDING = 0;
    case PROCESSED = 1;
    case REVIEWING = 2;
    case REJECTED = 3;
    case APPROVED = 4;

    /**
     * Statuses a moderator still has to act on: the moderation queue, the dashboard review
     * widget and the panel's default article scope all read this one list.
     *
     * @return list<self>
     */
    public static function awaitingReview(): array
    {
        return [self::PENDING, self::REVIEWING];
    }

    /**
     * @return list<int>
     */
    public static function awaitingReviewValues(): array
    {
        return array_map(static fn (self $status): int => $status->value, self::awaitingReview());
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PROCESSED => 'Processed',
            self::REVIEWING => 'Under Review',
            self::REJECTED => 'Rejected',
            self::APPROVED => 'Approved',
        };
    }
}
