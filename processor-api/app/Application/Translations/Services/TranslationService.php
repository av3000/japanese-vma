<?php

declare(strict_types=1);

namespace App\Application\Translations\Services;

use App\Application\Translations\Interfaces\Repositories\TranslationLocaleRepositoryInterface;
use App\Application\Translations\Interfaces\Repositories\TranslationOverrideRepositoryInterface;
use App\Application\Translations\Interfaces\Sources\EnglishSourceCatalogueInterface;
use App\Domain\Translations\DTOs\TranslationBundleDTO;
use App\Domain\Translations\Errors\TranslationErrors;
use App\Domain\Translations\Models\TranslationLocale;
use App\Domain\Translations\Services\TranslationResolver;
use App\Shared\Results\Result;

class TranslationService implements TranslationServiceInterface
{
    public function __construct(
        private readonly TranslationLocaleRepositoryInterface $locales,
        private readonly TranslationOverrideRepositoryInterface $overrides,
        private readonly EnglishSourceCatalogueInterface $english,
        private readonly TranslationResolver $resolver,
    ) {
    }

    public function activeLocales(): Result
    {
        return Result::success($this->locales->findActive());
    }

    /**
     * One query for the locale and one for its overrides, then the resolver merges them over
     * English in PHP, so the cost does not grow with the number of keys. English has no
     * overrides: it is the catalogue itself, and stored rows for it are never read.
     */
    public function bundle(string $code): Result
    {
        $locale = $this->locales->findActiveByCode($code);

        if ($locale === null) {
            return Result::failure(TranslationErrors::localeNotFound($code));
        }

        $source = $this->english->messages();
        $overrides = $locale->isFallback() ? [] : $this->overrides->overridesFor($locale->getIdValue());

        return Result::success(new TranslationBundleDTO(
            locale: $locale->getCode(),
            fallbackLocale: TranslationLocale::FALLBACK_CODE,
            messages: $this->resolver->merge($source, $overrides),
        ));
    }
}
