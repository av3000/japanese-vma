<?php

declare(strict_types=1);

namespace Tests\Feature\Routes;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

/**
 * RET-AUTH-01 boundary.
 *
 * The legacy session routes (`register`, `login`, `logout`, `user`) and the never-implemented
 * `testing` endpoint are gone. Three things that look adjacent are not: the `user/...` routes that
 * merely share a prefix, the `health` liveness probe, and the broadcasting authentication route.
 * This test guards every side of that line, because retiring the exact `user` path is one careless
 * edit away from taking the Catalogue and Articles routes with it.
 */
class LegacyAuthRouteRetirementTest extends TestCase
{
    public function test_retired_legacy_auth_routes_are_not_registered(): void
    {
        $retired = [
            ['POST', 'api/register'],
            ['POST', 'api/login'],
            ['GET', 'api/logout'],
            ['GET', 'api/user'],
            ['GET', 'api/testing'],
        ];

        foreach ($retired as [$method, $uri]) {
            $this->assertNull(
                $this->matchRoute($method, $uri),
                "Legacy auth route [{$method} {$uri}] is registered again; the v1 equivalent owns it."
            );
        }
    }

    /**
     * `Route::get('user', ...)` only ever matched the exact `api/user` path. Everything below shares
     * the prefix and belongs to the Articles and Catalogue lanes, which this slice does not touch.
     */
    public function test_sibling_user_prefixed_routes_are_still_registered(): void
    {
        $retained = [
            ['GET', 'api/user/articles'],
            ['GET', 'api/user/lists'],
            ['POST', 'api/user/lists/contain'],
            ['POST', 'api/user/list/contain'],
            ['POST', 'api/user/list/removeitemwhileaway'],
            ['POST', 'api/user/list/additemwhileaway'],
            ['GET', 'api/user/1/lists'],
        ];

        foreach ($retained as [$method, $uri]) {
            $this->assertNotNull(
                $this->matchRoute($method, $uri),
                "Route [{$method} {$uri}] disappeared; only the exact `api/user` path was retired by RET-AUTH-01."
            );
        }
    }

    public function test_v1_replacements_for_every_retired_route_are_registered(): void
    {
        $replacements = [
            ['POST', 'api/v1/register'],
            ['POST', 'api/v1/login'],
            ['POST', 'api/v1/logout'],
            ['GET', 'api/v1/me'],
        ];

        foreach ($replacements as [$method, $uri]) {
            $this->assertNotNull(
                $this->matchRoute($method, $uri),
                "v1 replacement [{$method} {$uri}] is missing; the legacy route it replaced is already gone."
            );
        }
    }

    /**
     * Both endpoints have an operational role rather than a session one, and neither has a v1
     * successor. They are documented exceptions in docs/architecture/deployment-and-runtime.md, so a
     * later `api.php` cleanup must not read them as leftovers.
     */
    public function test_retained_operational_endpoints_are_still_registered(): void
    {
        $this->assertNotNull(
            $this->matchRoute('GET', 'api/health'),
            'The health probe is an operational endpoint; RET-AUTH-01 retains it deliberately.'
        );

        $this->assertNotNull(
            $this->matchRoute('POST', 'api/broadcasting/auth'),
            'Broadcasting channel authorization is retained deliberately; it authorizes Echo channels, not sessions.'
        );
    }

    public function test_legacy_user_controller_is_gone(): void
    {
        $this->assertFalse(
            class_exists('App\Http\Controllers\UserController'),
            'The legacy UserController was reintroduced; the v1 AuthController owns register/login/logout/me.'
        );
    }

    private function matchRoute(string $method, string $uri): ?Route
    {
        $request = request()->create('/'.$uri, $method);

        foreach (RouteFacade::getRoutes() as $route) {
            if ($route->matches($request)) {
                return $route;
            }
        }

        return null;
    }
}
