<?php

declare(strict_types=1);

namespace App\Application\Translations\Services;

use App\Application\Translations\Interfaces\Repositories\TranslationLocaleRepositoryInterface;
use App\Application\Translations\Interfaces\Sources\EnglishSourceCatalogueInterface;
use App\Domain\Translations\DTOs\TranslationBundleDTO;
use App\Domain\Translations\Errors\TranslationErrors;
use App\Domain\Translations\Models\TranslationLocale;
use App\Shared\Results\Result;

class TranslationService implements TranslationServiceInterface
{
    public function __construct(
        private readonly TranslationLocaleRepositoryInterface $locales,
        private readonly EnglishSourceCatalogueInterface $english,
    ) {
    }

    public function activeLocales(): Result
    {
        return Result::success($this->locales->findActive());
    }

    /**
     * First cut: every active locale answers with the English catalogue, because no overrides
     * exist yet. Merging stored translations over it is the next story.
     */
    public function bundle(string $code): Result
    {
        $locale = $this->locales->findActiveByCode($code);

        if ($locale === null) {
            return Result::failure(TranslationErrors::localeNotFound($code));
        }

        return Result::success(new TranslationBundleDTO(
            locale: $locale->getCode(),
            fallbackLocale: TranslationLocale::FALLBACK_CODE,
            messages: $this->english->messages(),
        ));
    }
}
