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

        $name = trim($name);

        if (! $this->isValidName($name)) {
            return Result::failure(TranslationErrors::invalidLocaleName(self::MAX_NAME_LENGTH));
        }

        if ($this->locales->findByCode($code) !== null) {
            return Result::failure(TranslationErrors::localeCodeTaken($code));
        }

        return Result::success($this->locales->create($code, $name, $isActive));
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

        $name = trim($name);

        if (! $this->isValidName($name)) {
            return Result::failure(TranslationErrors::invalidLocaleName(self::MAX_NAME_LENGTH));
        }

        return Result::success($this->locales->update($locale->getIdValue(), $name, $isActive));
    }

    private function isValidName(string $name): bool
    {
        return $name !== '' && mb_strlen($name) <= self::MAX_NAME_LENGTH && mb_check_encoding($name, 'UTF-8') && ! str_contains($name, "\0");
    }
}
