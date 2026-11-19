<?php

declare(strict_types=1);

namespace App\Filament\Resources\TranslationLocales\Pages;

use App\Application\Translations\Services\TranslationLocaleAdminServiceInterface;
use App\Filament\Resources\TranslationLocales\Pages\Concerns\ReportsLocaleServiceErrors;
use App\Filament\Resources\TranslationLocales\TranslationLocaleResource;
use App\Filament\Support\PanelActor;
use App\Infrastructure\Persistence\Models\TranslationLocale;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTranslationLocale extends CreateRecord
{
    use ReportsLocaleServiceErrors;

    protected static string $resource = TranslationLocaleResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $result = app(TranslationLocaleAdminServiceInterface::class)->create(
            (string) ($data['code'] ?? ''),
            (string) ($data['name'] ?? ''),
            (bool) ($data['is_active'] ?? false),
            PanelActor::current(),
        );

        if ($result->isFailure()) {
            $this->reportLocaleServiceError($result);
        }

        return TranslationLocale::query()->findOrFail($result->getData()->getIdValue());
    }
}
