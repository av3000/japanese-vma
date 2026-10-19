<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Domain\Processing\Enums\ProcessingStatus;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\PublicityStatus;
use Filament\Support\Icons\Heroicon;

/**
 * Badge colours and icons for the statuses the panel shows. Every badge carries a label, so the
 * colour is never the only signal.
 */
final class StatusBadges
{
    public static function articleColor(ArticleStatus $status): string
    {
        return match ($status) {
            ArticleStatus::PENDING => 'gray',
            ArticleStatus::PROCESSED => 'info',
            ArticleStatus::REVIEWING => 'warning',
            ArticleStatus::REJECTED => 'danger',
            ArticleStatus::APPROVED => 'success',
        };
    }

    public static function publicityColor(PublicityStatus $publicity): string
    {
        return $publicity === PublicityStatus::PUBLIC ? 'success' : 'gray';
    }

    public static function publicityIcon(PublicityStatus $publicity): Heroicon
    {
        return $publicity === PublicityStatus::PUBLIC ? Heroicon::OutlinedGlobeAlt : Heroicon::OutlinedLockClosed;
    }

    public static function processingColor(?ProcessingStatus $status): string
    {
        return match ($status) {
            ProcessingStatus::COMPLETED => 'success',
            ProcessingStatus::FAILED => 'danger',
            ProcessingStatus::PROCESSING, ProcessingStatus::PENDING => 'warning',
            ProcessingStatus::SUPERSEDED, null => 'gray',
        };
    }

    public static function processingLabel(?ProcessingStatus $status): string
    {
        return $status === null ? 'Never processed' : ucfirst($status->value);
    }
}
