<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application\Users\Support\PanelPermission;
use App\Infrastructure\Persistence\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Two ways into Horizon, checked after the `web` middleware has started the session:
 *
 * 1. An admin panel session whose user holds `operations.view` - no second login.
 * 2. HTTP basic auth with the configured credentials, kept for non-browser access.
 *
 * A signed-in panel user without the permission gets 403 rather than a basic-auth prompt, unless
 * they send valid credentials anyway. A request with neither still gets the 401 challenge.
 */
class EnsureHorizonBasicAuth
{
    public const REQUEST_ATTRIBUTE = 'horizon.basic_auth_passed';

    public const PANEL_PERMISSION = PanelPermission::OperationsView;

    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local')) {
            $request->attributes->set(self::REQUEST_ATTRIBUTE, true);

            return $next($request);
        }

        $panelUser = $request->user('web');

        if ($panelUser instanceof User && $panelUser->hasPanelPermission(self::PANEL_PERMISSION)) {
            return $next($request);
        }

        if ($panelUser instanceof User && (string) $request->getUser() === '') {
            abort(403, 'Your account cannot open Horizon.');
        }

        // Read through config, not the environment helper, which returns null once config:cache has run.
        $expectedUsername = (string) config('horizon.basic_auth.username', '');
        $expectedPassword = (string) config('horizon.basic_auth.password', '');

        if ($expectedUsername === '' || $expectedPassword === '') {
            abort(403, 'Horizon basic auth credentials are not configured.');
        }

        $providedUsername = (string) $request->getUser();
        $providedPassword = (string) $request->getPassword();

        if (
            ! hash_equals($expectedUsername, $providedUsername) ||
            ! hash_equals($expectedPassword, $providedPassword)
        ) {
            return response('Unauthorized', 401, [
                'WWW-Authenticate' => 'Basic realm="Horizon"',
            ]);
        }

        $request->attributes->set(self::REQUEST_ATTRIBUTE, true);

        return $next($request);
    }

    /**
     * The viewHorizon gate: basic auth passed on this request, or the panel user may see operations.
     */
    public static function allows(Request $request, mixed $user): bool
    {
        if ($request->attributes->get(self::REQUEST_ATTRIBUTE, false) === true) {
            return true;
        }

        return $user instanceof User && $user->hasPanelPermission(self::PANEL_PERMISSION);
    }
}
