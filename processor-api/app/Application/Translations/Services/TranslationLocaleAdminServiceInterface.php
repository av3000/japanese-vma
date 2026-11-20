<?php

declare(strict_types=1);

namespace App\Application\Translations\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Shared\Results\Result;

/**
 * Panel-only: the administrator's side of the language list. Every method checks
 * `locales.manage` itself, so a crafted request is refused as the panel's buttons are.
 */
interface TranslationLocaleAdminServiceInterface
{
    /**
     * @return Result Success payload: TranslationLocale
     */
    public function create(string $code, string $name, bool $isActive, AuthenticatedUser $actor): Result;

    /**
     * Changes the name and the active flag of the locale with this code. The code itself never
     * changes, and English cannot be deactivated.
     *
     * @return Result Success payload: TranslationLocale
     */
    public function update(string $code, string $name, bool $isActive, AuthenticatedUser $actor): Result;

    /**
     * Switches the locale with this code on or off and leaves its name as stored, so a toggle
     * cannot overwrite a name changed elsewhere. English cannot be deactivated.
     *
     * @return Result Success payload: TranslationLocale
     */
    public function setActive(string $code, bool $isActive, AuthenticatedUser $actor): Result;
}
