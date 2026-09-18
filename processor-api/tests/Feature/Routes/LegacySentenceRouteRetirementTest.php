<?php

declare(strict_types=1);

namespace Tests\Feature\Routes;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

/**
 * RET-SEN-01 boundary.
 *
 * The legacy Sentence write routes and the Sentence comment routes - create, update, delete, the
 * comment CRUD and the comment like/unlike pair - are gone, and so is JapaneseDataController behind
 * them. The Post routes that shared the same `auth:api` group are line-for-line the same shape as
 * the ones this slice removed, so both sides of that line are asserted here.
 */
class LegacySentenceRouteRetirementTest extends TestCase
{
    public function test_retired_legacy_sentence_routes_are_not_registered(): void
    {
        $retired = [
            // Writes
            ['POST', 'api/sentence'],
            ['PUT', 'api/sentence/1'],
            ['DELETE', 'api/sentence/1'],
            // Comments
            ['POST', 'api/sentence/1/comment'],
            ['PUT', 'api/sentence/1/comment/2'],
            ['DELETE', 'api/sentence/1/comment/2'],
            ['POST', 'api/sentence/1/comment/2/like'],
            ['POST', 'api/sentence/1/comment/2/unlike'],
        ];

        foreach ($retired as [$method, $uri]) {
            $this->assertNull(
                $this->matchRoute($method, $uri),
                "Legacy Sentence route [{$method} {$uri}] is registered again; the v1 equivalent owns it."
            );
        }
    }

    public function test_v1_replacements_for_every_retired_route_are_registered(): void
    {
        $uuid = '11111111-2222-4333-8444-555555555555';

        $replacements = [
            ['POST', 'api/v1/sentences'],
            ['PUT', "api/v1/sentences/{$uuid}"],
            ['DELETE', "api/v1/sentences/{$uuid}"],
            ['GET', "api/v1/sentences/{$uuid}/comments"],
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
     * Article and List retirements made. A future reader finding no `unlike` route at v1 must not
     * read that as an unfinished migration.
     */
    public function test_like_and_unlike_are_served_by_the_single_v1_toggle(): void
    {
        $this->assertNotNull(
            $this->matchRoute('POST', 'api/v1/like-instance'),
            'The v1 like toggle is missing; it replaced both the like and the unlike comment routes.'
        );
        $this->assertNull(
            $this->matchRoute('POST', 'api/v1/unlike-instance'),
            'A separate v1 unlike route exists; the toggle is the contract.'
        );
    }

    /**
     * The controller had no callers left once these routes went, and its three text helpers had
     * no caller outside the retired writes. The same-named global functions in app/Helpers are
     * Article-typed and unrelated; they stay for the seeders.
     */
    public function test_legacy_japanese_controller_is_gone(): void
    {
        $this->assertFalse(
            class_exists('App\Http\Controllers\JapaneseDataController'),
            'The legacy JapaneseDataController was reintroduced; the v1 Sentence and Comment controllers own this domain.'
        );

        $this->assertTrue(
            function_exists('getKanjiIdsFromText'),
            'The Article-typed global helper in app/Helpers was removed along with the controller; ArticlesTableSeeder still calls it.'
        );
    }

    /**
     * These sit in the same `auth:api` group as the removed routes and are the same shape. They
     * belong to the Post lane, which RET-POST-01 retires, not this slice.
     */
    public function test_sibling_legacy_post_routes_are_still_registered(): void
    {
        $retained = [
            ['POST', 'api/post'],
            ['PUT', 'api/post/1'],
            ['DELETE', 'api/post/1'],
            ['POST', 'api/post/1/comment'],
            ['PUT', 'api/post/1/comment/2'],
            ['DELETE', 'api/post/1/comment/2'],
            ['POST', 'api/post/1/comment/2/like'],
            ['POST', 'api/post/1/comment/2/unlike'],
            ['GET', 'api/posts'],
            ['GET', 'api/post/1'],
            ['POST', 'api/posts/search'],
            ['GET', 'api/health'],
        ];

        foreach ($retained as [$method, $uri]) {
            $this->assertNotNull(
                $this->matchRoute($method, $uri),
                "Route [{$method} {$uri}] disappeared; RET-SEN-01 retires the Sentence family only."
            );
        }
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
