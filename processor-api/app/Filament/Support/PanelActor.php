<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Infrastructure\Auth\Providers\PassportCurrentUserProvider;
use App\Infrastructure\Persistence\Models\User;
use Filament\Facades\Filament;
use LogicException;

/**
 * The signed-in panel user as the application layer sees it. Uses the same mapping as the API's
 * current-user provider, so application policies judge a panel action exactly as they would the
 * same person calling the API.
 */
final class PanelActor
{
    public static function user(): User
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            throw new LogicException('The admin panel must be signed in with a persistence User.');
        }

        return $user;
    }

    public static function current(): AuthenticatedUser
    {
        return PassportCurrentUserProvider::authenticatedUserFor(self::user());
    }

    /**
     * Panel permission plus the application rule. Write actions render only when both pass, so
     * nobody is shown an action the application layer would then refuse.
     *
     * @param callable(AuthenticatedUser): bool|null $applicationRule
     */
    public static function may(string $permission, ?callable $applicationRule = null): bool
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User || ! $user->hasPanelPermission($permission)) {
            return false;
        }

        return $applicationRule === null || $applicationRule(self::current());
    }
}
