<?php

declare(strict_types=1);

namespace App\Application\Translations\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Translations\Interfaces\Repositories\TranslationLocaleRepositoryInterface;
use App\Application\Translations\Policies\TranslationLocaleManagementPolicy;
use App\Domain\Translations\Errors\TranslationErrors;
use App\Shared\Results\Result;

class TranslationLocaleAdminService implements TranslationLocaleAdminServiceInterface
{
    public const MAX_NAME_LENGTH = 100;

    /** Lowercase ISO 639-1: two letters, no region (`pt-BR` is out of scope). The `D` flag stops `$`
     * from accepting a trailing newline. */
    private const CODE_PATTERN = '/^[a-z]{2}$/D';

    public function __construct(
        private readonly TranslationLocaleRepositoryInterface $locales,
        private readonly TranslationLocaleManagementPolicy $policy,
    ) {
    }

    public function create(string $code, string $name, bool $isActive, AuthenticatedUser $actor): Result
    {
        if (! $this->policy->canManage($actor)) {
            return Result::failure(TranslationErrors::manageLocalesDenied());
        }

        if (preg_match(self::CODE_PATTERN, $code) !== 1) {
            return Result::failure(TranslationErrors::invalidLocaleCode());
        }

        $name = $this->cleanName($name);

        if ($name === null) {
            return Result::failure(TranslationErrors::invalidLocaleName(self::MAX_NAME_LENGTH));
        }

        if ($this->locales->findByCode($code) !== null) {
            return Result::failure(TranslationErrors::localeCodeTaken($code));
        }

        // The pre-check gives the common case its message; the repository answers null when a
        // concurrent create won the unique index between the check and the insert.
        $created = $this->locales->create($code, $name, $isActive);

        return $created === null
            ? Result::failure(TranslationErrors::localeCodeTaken($code))
            : Result::success($created);
    }

    public function update(string $code, string $name, bool $isActive, AuthenticatedUser $actor): Result
    {
        if (! $this->policy->canManage($actor)) {
            return Result::failure(TranslationErrors::manageLocalesDenied());
        }

        $locale = $this->locales->findByCode($code);

        if ($locale === null) {
            return Result::failure(TranslationErrors::localeNotFound($code));
        }

        // The locale is picked by its code, so a code cannot be changed through here at all.
        if ($locale->isFallback() && ! $isActive) {
            return Result::failure(TranslationErrors::sourceLocaleLocked());
        }

        $name = $this->cleanName($name);

        if ($name === null) {
            return Result::failure(TranslationErrors::invalidLocaleName(self::MAX_NAME_LENGTH));
        }

        return Result::success($this->locales->update($locale->getIdValue(), $name, $isActive));
    }

    public function setActive(string $code, bool $isActive, AuthenticatedUser $actor): Result
    {
        if (! $this->policy->canManage($actor)) {
            return Result::failure(TranslationErrors::manageLocalesDenied());
        }

        $locale = $this->locales->findByCode($code);

        if ($locale === null) {
            return Result::failure(TranslationErrors::localeNotFound($code));
        }

        if ($locale->isFallback() && ! $isActive) {
            return Result::failure(TranslationErrors::sourceLocaleLocked());
        }

        // The name is read here, not passed in, so a toggle never writes back a name that another
        // administrator changed after the table was drawn.
        return Result::success($this->locales->update($locale->getIdValue(), $locale->getName(), $isActive));
    }

    /**
     * The name without surrounding whitespace, or null when it cannot be stored or shown.
     *
     * With the `u` flag, `\s` and `\p{Z}` cover U+3000 and no-break spaces and `\p{Cf}` covers
     * zero-width characters, so a name made only of those is empty too.
     */
    private function cleanName(string $name): ?string
    {
        if (! mb_check_encoding($name, 'UTF-8') || str_contains($name, "\0")) {
            return null;
        }

        $trimmed = preg_replace('/^[\s\p{Z}\p{Cf}]+|[\s\p{Z}\p{Cf}]+$/u', '', $name);

        if ($trimmed === null || $trimmed === '' || mb_strlen($trimmed) > self::MAX_NAME_LENGTH) {
            return null;
        }

        return $trimmed;
    }
}
