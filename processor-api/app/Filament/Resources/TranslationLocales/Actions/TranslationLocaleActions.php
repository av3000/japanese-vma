<?php

declare(strict_types=1);

namespace App\Filament\Resources\TranslationLocales\Actions;

use App\Application\Translations\Services\TranslationLocaleAdminServiceInterface;
use App\Filament\Support\ActionFormErrors;
use App\Filament\Support\PanelActor;
use App\Filament\Support\ResultNotifications;
use App\Infrastructure\Persistence\Models\TranslationLocale;
use App\Shared\Results\Result;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;

/**
 * Create and edit run as modals on the list page: the form is three fields, so a page of its own
 * only costs a reload. Both write through TranslationLocaleAdminService, never through Eloquent.
 */
final class TranslationLocaleActions
{
    public static function create(): CreateAction
    {
        return CreateAction::make()
            ->using(static function (array $data, CreateAction $action): TranslationLocale {
                $result = app(TranslationLocaleAdminServiceInterface::class)->create(
                    (string) ($data['code'] ?? ''),
                    (string) ($data['name'] ?? ''),
                    (bool) ($data['is_active'] ?? false),
                    PanelActor::current(),
                );

                if ($result->isFailure()) {
                    self::reportFailure($action, $result);
                }

                return TranslationLocale::query()->findOrFail($result->getData()->getIdValue());
            });
    }

    public static function edit(): EditAction
    {
        return EditAction::make()
            ->using(static function (TranslationLocale $record, array $data, EditAction $action): TranslationLocale {
                // The code is the record's own: a submitted `code` is ignored, and a disabled toggle
                // (English) is not submitted, so the current flag stands.
                $result = app(TranslationLocaleAdminServiceInterface::class)->update(
                    $record->code,
                    (string) ($data['name'] ?? $record->name),
                    (bool) ($data['is_active'] ?? $record->is_active),
                    PanelActor::current(),
                );

                if ($result->isFailure()) {
                    self::reportFailure($action, $result);
                }

                return $record->refresh();
            });
    }

    /**
     * A refusal about one field shows under that field and keeps the modal open; anything else is a
     * notification, and the action stops without writing.
     */
    private static function reportFailure(Action $action, Result $result): never
    {
        $field = match ($result->getError()->code) {
            'Translations.InvalidLocaleCode', 'Translations.LocaleCodeTaken' => 'code',
            'Translations.InvalidLocaleName' => 'name',
            default => null,
        };

        if ($field !== null) {
            ActionFormErrors::throw($action, $field, ResultNotifications::message($result));
        }

        ResultNotifications::failure($result);

        $action->halt();
    }
}
