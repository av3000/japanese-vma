<?php

declare(strict_types=1);

namespace App\Filament\Resources\TranslationLocales\Pages\Concerns;

use App\Filament\Support\ResultNotifications;
use App\Shared\Results\Result;
use Illuminate\Validation\ValidationException;

/**
 * Turns a TranslationLocaleAdminService failure into panel feedback: a problem with one field sits
 * under that field, anything else (no permission, English locked) becomes a notification.
 */
trait ReportsLocaleServiceErrors
{
    protected function reportLocaleServiceError(Result $result): never
    {
        $field = match ($result->getError()->code) {
            'Translations.InvalidLocaleCode', 'Translations.LocaleCodeTaken' => 'data.code',
            'Translations.InvalidLocaleName' => 'data.name',
            default => null,
        };

        if ($field !== null) {
            throw ValidationException::withMessages([$field => ResultNotifications::message($result)]);
        }

        ResultNotifications::failure($result);

        $this->halt();
    }
}
