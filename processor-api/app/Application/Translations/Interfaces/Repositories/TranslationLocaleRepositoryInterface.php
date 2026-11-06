<?php

declare(strict_types=1);

namespace App\Application\Translations\Interfaces\Repositories;

use App\Domain\Translations\Models\TranslationLocale;

interface TranslationLocaleRepositoryInterface
{
    /**
     * Every active locale, English first, then the rest by code.
     *
     * @return list<TranslationLocale>
     */
    public function findActive(): array;

    /** The locale with this exact code, only when it is active. */
    public function findActiveByCode(string $code): ?TranslationLocale;
}
