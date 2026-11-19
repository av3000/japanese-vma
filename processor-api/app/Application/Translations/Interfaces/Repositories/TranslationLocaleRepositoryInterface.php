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

    /**
     * The locale with this exact code, active or not. The edit side needs it to tell an unknown
     * locale from a deactivated one.
     */
    public function findByCode(string $code): ?TranslationLocale;

    /** Adds a language. The caller has already checked the code is free. */
    public function create(string $code, string $name, bool $isActive): TranslationLocale;

    /** Changes the name and the active flag. The code never changes. */
    public function update(int $id, string $name, bool $isActive): TranslationLocale;
}
