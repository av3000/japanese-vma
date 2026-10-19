<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Shared\Results\Result;
use Filament\Notifications\Notification;

/**
 * Turns application Results into panel notifications, so every action reports failures with the
 * service's own message and bulk actions report partial failures the same way.
 */
final class ResultNotifications
{
    /**
     * @return bool whether the result succeeded
     */
    public static function send(Result $result, string $successTitle): bool
    {
        if ($result->isFailure()) {
            self::failure($result);

            return false;
        }

        Notification::make()->success()->title($successTitle)->send();

        return true;
    }

    public static function failure(Result $result): void
    {
        Notification::make()
            ->danger()
            ->title($result->getError()->errorMessage ?? $result->getError()->description)
            ->send();
    }

    public static function message(Result $result): string
    {
        return $result->getError()->errorMessage ?? $result->getError()->description;
    }

    /**
     * "3 changed, 1 failed", with the first few failures listed by name.
     *
     * @param list<string> $failures "label: reason" per failed record
     */
    public static function bulk(int $total, array $failures, string $verb): void
    {
        $succeeded = $total - count($failures);

        Notification::make()
            ->title("{$succeeded} {$verb}, ".count($failures).' failed')
            ->body($failures === [] ? null : implode("\n", array_slice($failures, 0, 5)))
            ->status($failures === [] ? 'success' : 'warning')
            ->send();
    }
}
