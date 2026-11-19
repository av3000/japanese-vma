<?php

declare(strict_types=1);

namespace App\Application\Translations\Services;

use App\Application\Translations\Interfaces\Repositories\TranslationLocaleRepositoryInterface;
use App\Application\Translations\Interfaces\Repositories\TranslationOverrideRepositoryInterface;
use App\Application\Translations\Interfaces\Sources\EnglishSourceCatalogueInterface;
use App\Domain\Translations\DTOs\TranslationRowDTO;
use App\Domain\Translations\Enums\TranslationState;
use App\Domain\Translations\Errors\TranslationErrors;
use App\Domain\Translations\Models\TranslationLocale;
use App\Domain\Translations\Services\TranslationResolver;
use App\Shared\Results\Result;

class TranslationEditorService implements TranslationEditorServiceInterface
{
    /** Characters, not bytes: Danish and Japanese text must not be cut short by multi-byte letters. */
    public const MAX_VALUE_LENGTH = 2000;

    public function __construct(
        private readonly TranslationLocaleRepositoryInterface $locales,
        private readonly TranslationOverrideRepositoryInterface $overrides,
        private readonly EnglishSourceCatalogueInterface $english,
        private readonly TranslationResolver $resolver,
    ) {
    }

    public function completion(string $code): Result
    {
        $locale = $this->editableLocale($code);

        if ($locale instanceof Result) {
            return $locale;
        }

        return Result::success($this->resolver->completion(
            $this->english->messages(),
            $this->overrides->overridesFor($locale->getIdValue()),
        ));
    }

    public function saveOverride(string $code, string $key, string $raw, ?int $userId): Result
    {
        $locale = $this->editableLocale($code);

        if ($locale instanceof Result) {
            return $locale;
        }

        if ($this->english->find($key) === null) {
            return Result::failure(TranslationErrors::unknownKey($key));
        }

        $value = trim($raw);

        if (! mb_check_encoding($value, 'UTF-8') || str_contains($value, "\0")) {
            return Result::failure(TranslationErrors::invalidValue());
        }

        if (mb_strlen($value) > self::MAX_VALUE_LENGTH) {
            return Result::failure(TranslationErrors::valueTooLong(self::MAX_VALUE_LENGTH));
        }

        if ($value === '') {
            $this->overrides->delete($locale->getIdValue(), $key);
        } else {
            $this->overrides->upsert($locale->getIdValue(), $key, $value, $userId);
        }

        return Result::success($this->resolver->state($value));
    }

    public function rows(string $code, ?string $search, bool $missingOnly): Result
    {
        $locale = $this->editableLocale($code);

        if ($locale instanceof Result) {
            return $locale;
        }

        $overrides = $this->overrides->overridesFor($locale->getIdValue());
        $needle = $search === null ? '' : mb_strtolower(trim($search));
        $rows = [];

        foreach ($this->english->messages() as $key => $source) {
            $state = $this->resolver->state($overrides[$key] ?? null);

            if ($missingOnly && $state === TranslationState::Translated) {
                continue;
            }

            $value = $state === TranslationState::Translated ? trim($overrides[$key]) : null;

            if ($needle !== '' && ! $this->matches($needle, [$key, $source, $value])) {
                continue;
            }

            $rows[] = new TranslationRowDTO($key, $source, $value, $state);
        }

        return Result::success($rows);
    }

    public function editableLocales(): array
    {
        return array_values(array_filter(
            $this->locales->findActive(),
            static fn (TranslationLocale $locale): bool => ! $locale->isFallback(),
        ));
    }

    /**
     * @param list<string|null> $haystacks
     */
    private function matches(string $needle, array $haystacks): bool
    {
        foreach ($haystacks as $haystack) {
            if ($haystack !== null && str_contains(mb_strtolower($haystack), $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The locale a translator may work on: it exists, is active, and is not English.
     * Returns the failure Result when it is not.
     *
     * English is refused by its code before the lookup, so the refusal does not depend on an
     * `en` row existing. The bundle's `isFallback()` compares the same constant, so the two agree.
     */
    private function editableLocale(string $code): TranslationLocale|Result
    {
        if ($code === TranslationLocale::FALLBACK_CODE) {
            return Result::failure(TranslationErrors::sourceLocaleNotEditable());
        }

        $locale = $this->locales->findByCode($code);

        if ($locale === null) {
            return Result::failure(TranslationErrors::localeNotFound($code));
        }

        if (! $locale->isActive()) {
            return Result::failure(TranslationErrors::localeInactive($code));
        }

        return $locale;
    }
}
