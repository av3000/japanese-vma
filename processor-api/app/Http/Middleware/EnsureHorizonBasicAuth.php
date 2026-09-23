<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHorizonBasicAuth
{
    public const REQUEST_ATTRIBUTE = 'horizon.basic_auth_passed';

    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local')) {
            $request->attributes->set(self::REQUEST_ATTRIBUTE, true);

            return $next($request);
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
}
