<?php

declare(strict_types=1);

namespace App\Application\Translations\Services;

use App\Shared\Results\Result;

interface TranslationServiceInterface
{
    /**
     * The locales a client may choose from.
     *
     * Success data: `list<TranslationLocale>`, English first.
     */
    public function activeLocales(): Result;

    /**
     * The complete bundle for one active locale.
     *
     * Success data: `TranslationBundleDTO`. Failure: `TranslationErrors::localeNotFound`, for an
     * unknown and for an inactive code alike.
     */
    public function bundle(string $code): Result;
}
