<?php

declare(strict_types=1);

namespace App\Application\Translations\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Translations\Models\TranslationLocale;
use App\Shared\Results\Result;

/**
 * The edit side of translations, used by Filament only. The public HTTP side is
 * `TranslationServiceInterface`.
 */
interface TranslationEditorServiceInterface
{
    /**
     * How much of English one active non-English locale has translated.
     *
     * Success data: `TranslationCompletionDTO`. Failures: `TranslationErrors::localeNotFound`,
     * `::localeInactive`, `::sourceLocaleNotEditable` (English has no completion figure).
     */
    public function completion(string $code): Result;

    /**
     * Save, or clear, the translation of one key.
     *
     * Needs `translations.update`: the panel hides its Save action, but this is the boundary, so a
     * command or route calling it is refused too.
     *
     * The value is trimmed. Text is upserted and records the actor; a blank value deletes the
     * override, so the key falls back to English. Nothing is written on a failure.
     *
     * Success data: the new `TranslationState`. Failures: `TranslationErrors::updateDenied`,
     * `::sourceLocaleNotEditable`, `::localeNotFound`, `::localeInactive`, `::unknownKey`,
     * `::invalidValue`, `::valueTooLong`.
     */
    public function saveOverride(string $code, string $key, string $raw, AuthenticatedUser $actor): Result;

    /**
     * Every English key for one locale, in English order, so a key with no stored row is listed too.
     * Orphan overrides (keys gone from English) never appear.
     *
     * `$search` is matched case-insensitively against the key, the English text and the translation;
     * null or blank searches nothing. `$missingOnly` keeps the keys without a valid translation.
     * Built from the catalogue and one overrides query, whatever the key count.
     *
     * Success data: `list<TranslationRowDTO>`. Failures: `TranslationErrors::sourceLocaleNotEditable`,
     * `::localeNotFound`, `::localeInactive`.
     */
    public function rows(string $code, ?string $search, bool $missingOnly): Result;

    /**
     * The locales a translator can work on: active and not English.
     *
     * @return list<TranslationLocale>
     */
    public function editableLocales(): array;
}
