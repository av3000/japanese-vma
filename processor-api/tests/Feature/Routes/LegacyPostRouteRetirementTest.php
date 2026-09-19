<?php

declare(strict_types=1);

namespace Tests\Feature\Routes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

/**
 * RET-POST-01 boundary.
 *
 * The whole legacy Community Post family - list, search, detail, create, update, delete, both lock
 * paths, like/unlike/checklike, the comment CRUD and the comment like/unlike pair - is gone, and so
 * is PostController behind it. It was the last legacy family in routes/api.php, so the only
 * non-v1 routes left under the `api` prefix are the operational ones.
 */
class LegacyPostRouteRetirementTest extends TestCase
{
    public function test_retired_legacy_post_routes_are_not_registered(): void
    {
        $retired = [
            // Reads
            ['GET', 'api/posts'],
            ['GET', 'api/post/1'],
            ['POST', 'api/posts/search'],
            // Writes
            ['POST', 'api/post'],
            ['PUT', 'api/post/1'],
            ['DELETE', 'api/post/1'],
            // Moderation: the unguarded path and its admin-guarded duplicate
            ['POST', 'api/post/1/toggleLock'],
            ['POST', 'api/post/1/togglelock'],
            // Likes
            ['POST', 'api/post/1/like'],
            ['POST', 'api/post/1/unlike'],
            ['POST', 'api/post/1/checklike'],
            // Comments
            ['POST', 'api/post/1/comment'],
            ['PUT', 'api/post/1/comment/2'],
            ['DELETE', 'api/post/1/comment/2'],
            ['POST', 'api/post/1/comment/2/like'],
            ['POST', 'api/post/1/comment/2/unlike'],
        ];

        foreach ($retired as [$method, $uri]) {
            $this->assertNull(
                $this->matchRoute($method, $uri),
                "Legacy Post route [{$method} {$uri}] is registered again; the v1 equivalent owns it."
            );
        }
    }

    public function test_v1_replacements_for_every_retired_route_are_registered(): void
    {
        $uuid = '11111111-2222-4333-8444-555555555555';

        $replacements = [
            ['GET', 'api/v1/posts'],
            ['GET', "api/v1/posts/{$uuid}"],
            // The transitional numeric identifier resolves through the same v1 detail route.
            ['GET', 'api/v1/posts/1'],
            ['POST', 'api/v1/posts'],
            ['PUT', "api/v1/posts/{$uuid}"],
            ['DELETE', "api/v1/posts/{$uuid}"],
            ['PUT', "api/v1/posts/{$uuid}/lock"],
            ['GET', "api/v1/posts/{$uuid}/comments"],
            ['POST', 'api/v1/comments'],
            ['PUT', "api/v1/comments/{$uuid}"],
            ['DELETE', "api/v1/comments/{$uuid}"],
            ['POST', 'api/v1/like-instance'],
        ];

        foreach ($replacements as [$method, $uri]) {
            $this->assertNotNull(
                $this->matchRoute($method, $uri),
                "v1 replacement [{$method} {$uri}] is missing; the legacy route it replaced is already gone."
            );
        }
    }

    /**
     * The legacy like and unlike routes collapse into one idempotent toggle, the same decision the
     * Article, List and Sentence retirements made, and `checklike` is answered by the `engagement`
     * block on the v1 detail. A future reader finding no `unlike` or `checklike` route at v1 must
     * not read that as an unfinished migration.
     */
    public function test_like_unlike_and_checklike_are_served_by_the_single_v1_toggle_and_the_detail(): void
    {
        $this->assertNotNull(
            $this->matchRoute('POST', 'api/v1/like-instance'),
            'The v1 like toggle is missing; it replaced the like, unlike and comment-like routes.'
        );
        $this->assertNull(
            $this->matchRoute('POST', 'api/v1/unlike-instance'),
            'A separate v1 unlike route exists; the toggle is the contract.'
        );
        $this->assertNull(
            $this->matchRoute('POST', 'api/v1/posts/1/checklike'),
            'A v1 checklike route exists; viewer like state is `engagement` on the detail payload.'
        );
    }

    /**
     * Legacy exposed the lock twice: `toggleLock` in the plain `auth:api` group with no role check,
     * and `togglelock` behind the admin guard. v1 keeps exactly one route, and it takes the desired
     * state rather than toggling.
     */
    public function test_lock_is_served_by_a_single_explicit_state_v1_route(): void
    {
        $uuid = '11111111-2222-4333-8444-555555555555';

        $this->assertNotNull(
            $this->matchRoute('PUT', "api/v1/posts/{$uuid}/lock"),
            'The v1 lock route is missing; it replaced both legacy toggle paths.'
        );
        $this->assertNull(
            $this->matchRoute('POST', "api/v1/posts/{$uuid}/toggleLock"),
            'A v1 toggle-shaped lock route exists; the explicit-state PUT is the contract.'
        );
    }

    /**
     * The controller had no callers left once these routes went, its form request had one route,
     * and the `whereLike` Builder macro had one caller in PostController@generateQuery. The legacy
     * models under App\Http\Models stay: the Article actions and App\Http\User still use them.
     */
    public function test_legacy_post_controller_request_and_macro_are_gone(): void
    {
        $this->assertFalse(
            class_exists('App\Http\Controllers\PostController'),
            'The legacy PostController was reintroduced; the v1 Post, Comment and Like controllers own this domain.'
        );
        $this->assertFalse(
            class_exists('App\Http\Requests\PostStoreRequest'),
            'The legacy PostStoreRequest was reintroduced; its only route is gone.'
        );
        $this->assertFalse(
            Builder::hasGlobalMacro('whereLike'),
            'The whereLike Builder macro was reintroduced; its only caller was the retired PostController.'
        );

        $this->assertTrue(
            class_exists('App\Http\Models\Post'),
            'The legacy Post model was removed along with the controller; App\Http\User and the persistence ObjectTemplate still reference it.'
        );
    }

    /**
     * With the Post family gone, nothing outside v1 is registered under the `api` prefix except
     * the container liveness probe and the broadcasting authentication route that
     * BroadcastServiceProvider adds. RET-AUTH-01 retained both on purpose.
     */
    public function test_only_operational_routes_remain_outside_v1(): void
    {
        $this->assertNotNull(
            $this->matchRoute('GET', 'api/health'),
            'Route [GET api/health] disappeared; RET-POST-01 retires the Post family only.'
        );

        $nonV1 = [];
        foreach (RouteFacade::getRoutes() as $route) {
            $uri = $route->uri();
            if (str_starts_with($uri, 'api/') && ! str_starts_with($uri, 'api/v1/')) {
                $nonV1[] = $uri;
            }
        }

        $this->assertEqualsCanonicalizing(
            ['api/health', 'api/broadcasting/auth'],
            array_values(array_unique($nonV1)),
            'A non-v1 route is registered under the api prefix; every legacy family has been retired.'
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
