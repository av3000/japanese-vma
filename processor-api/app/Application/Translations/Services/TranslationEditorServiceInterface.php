<?php

declare(strict_types=1);

namespace App\Application\Translations\Services;

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
     * The value is trimmed. Text is upserted and records `$userId`; a blank value deletes the
     * override, so the key falls back to English. Nothing is written on a failure.
     *
     * Success data: the new `TranslationState`. Failures: `TranslationErrors::sourceLocaleNotEditable`,
     * `::localeNotFound`, `::localeInactive`, `::unknownKey`, `::valueTooLong`.
     */
    public function saveOverride(string $code, string $key, string $raw, ?int $userId): Result;
}
