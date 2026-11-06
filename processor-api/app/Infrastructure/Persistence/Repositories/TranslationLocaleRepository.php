<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Translations\Interfaces\Repositories\TranslationLocaleRepositoryInterface;
use App\Domain\Translations\Models\TranslationLocale as DomainTranslationLocale;
use App\Infrastructure\Persistence\Models\TranslationLocale;

class TranslationLocaleRepository implements TranslationLocaleRepositoryInterface
{
    public function findActive(): array
    {
        return TranslationLocale::query()
            ->where('is_active', true)
            // English leads the list; the other languages follow in code order.
            ->orderByRaw('code = ? desc', [DomainTranslationLocale::FALLBACK_CODE])
            ->orderBy('code')
            ->get()
            ->map(fn (TranslationLocale $locale): DomainTranslationLocale => $this->toDomain($locale))
            ->all();
    }

    public function findActiveByCode(string $code): ?DomainTranslationLocale
    {
        $locale = TranslationLocale::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        return $locale === null ? null : $this->toDomain($locale);
    }

    private function toDomain(TranslationLocale $locale): DomainTranslationLocale
    {
        return new DomainTranslationLocale(
            id: (int) $locale->id,
            code: (string) $locale->code,
            name: (string) $locale->name,
            isActive: (bool) $locale->is_active,
        );
    }
}
