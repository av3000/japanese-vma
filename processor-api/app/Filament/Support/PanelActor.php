<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Application\Activity\ActivitySourceContext;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Activity\Enums\ActivitySource;
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

    /**
     * Every panel write resolves its actor here, so this is also where the request is marked as
     * coming from the panel: Livewire action requests do not run the panel's page middleware.
     *
     * The static provider call is deliberate: CurrentUserProviderInterface resolves only the `api`
     * guard, while the panel signs in on `web`.
     */
    public static function current(): AuthenticatedUser
    {
        app(ActivitySourceContext::class)->set(ActivitySource::Panel);

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
