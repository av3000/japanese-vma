<?php

declare(strict_types=1);

namespace App\Filament\Resources\TranslationLocales\Pages;

use App\Application\Translations\Services\TranslationLocaleAdminServiceInterface;
use App\Filament\Resources\TranslationLocales\Pages\Concerns\ReportsLocaleServiceErrors;
use App\Filament\Resources\TranslationLocales\TranslationLocaleResource;
use App\Filament\Support\PanelActor;
use App\Infrastructure\Persistence\Models\TranslationLocale;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditTranslationLocale extends EditRecord
{
    use ReportsLocaleServiceErrors;

    protected static string $resource = TranslationLocaleResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var TranslationLocale $record */
        // The code is the record's own: a submitted `code` is ignored, and a disabled toggle (English)
        // is not submitted, so the current flag stands.
        $result = app(TranslationLocaleAdminServiceInterface::class)->update(
            $record->code,
            (string) ($data['name'] ?? $record->name),
            (bool) ($data['is_active'] ?? $record->is_active),
            PanelActor::current(),
        );

        if ($result->isFailure()) {
            $this->reportLocaleServiceError($result);
        }

        return $record->refresh();
    }
}
