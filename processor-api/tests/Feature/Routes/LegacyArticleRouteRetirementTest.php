<?php

declare(strict_types=1);

namespace Tests\Feature\Routes;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

/**
 * RET-ART-01 boundary.
 *
 * The legacy Article route family - public reads and search, CRUD, PDF exports, moderation, the
 * Article comment endpoints and the Article like endpoints - is gone, and so is the controller
 * behind it. Three neighbourhoods sit close enough to be taken by a careless edit: the `articles`
 * and `article` prefixes are one character apart, `user/articles` shares a prefix with the whole
 * Catalogue lane, and the List and Post comment routes are line-for-line the same shape as the
 * Article ones this slice removed. Every side of that line is asserted here.
 */
class LegacyArticleRouteRetirementTest extends TestCase
{
    public function test_retired_legacy_article_routes_are_not_registered(): void
    {
        $retired = [
            // Public reads and search
            ['GET', 'api/articles'],
            ['GET', 'api/article/1'],
            ['GET', 'api/article/1/kanjis'],
            ['GET', 'api/article/1/words'],
            ['POST', 'api/articles/search'],
            // CRUD
            ['POST', 'api/article'],
            ['PUT', 'api/article/1'],
            ['DELETE', 'api/article/1'],
            ['GET', 'api/user/articles'],
            ['POST', 'api/article/1/togglepublicity'],
            // PDF exports
            ['GET', 'api/article/1/kanjis-pdf'],
            ['GET', 'api/article/1/words-pdf'],
            // Likes
            ['POST', 'api/article/1/like'],
            ['POST', 'api/article/1/unlike'],
            ['POST', 'api/article/1/checklike'],
            // Comments
            ['POST', 'api/article/1/comment'],
            ['DELETE', 'api/article/comment/1'],
            ['PUT', 'api/article/1/comment/1'],
            ['POST', 'api/article/1/comment/1/like'],
            ['POST', 'api/article/1/comment/1/unlike'],
            // Moderation
            ['POST', 'api/article/1/setstatus'],
            ['GET', 'api/article/1/getstatus'],
            ['GET', 'api/articles/pendinglist'],
        ];

        foreach ($retired as [$method, $uri]) {
            $this->assertNull(
                $this->matchRoute($method, $uri),
                "Legacy Article route [{$method} {$uri}] is registered again; the v1 equivalent owns it."
            );
        }
    }

    public function test_v1_replacements_for_every_retired_route_are_registered(): void
    {
        $uuid = '11111111-2222-4333-8444-555555555555';

        $replacements = [
            ['GET', 'api/v1/articles'],
            ['GET', 'api/v1/articles/1'],
            // The words replacement is the word index filtered by article (#268): one query
            // path per resource, rather than a second article-scoped route beside it.
            ['GET', 'api/v1/words'],
            ['GET', 'api/v1/kanjis'],
            ['POST', 'api/v1/articles'],
            ['PUT', "api/v1/articles/{$uuid}"],
            ['DELETE', "api/v1/articles/{$uuid}"],
            ['GET', "api/v1/articles/{$uuid}/kanjis-pdf"],
            ['GET', "api/v1/articles/{$uuid}/words-pdf"],
            ['GET', 'api/v1/articles/pending'],
            ['POST', "api/v1/articles/{$uuid}/status"],
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
     * Four legacy capabilities have no same-shaped v1 endpoint, by decision rather than by omission.
     * The replacement is a field on a payload the caller already fetches, so a future reader finding
     * no `getstatus` route must not read that as an unfinished migration.
     *
     * - `checklike`     -> `engagement` on the v1 article detail
     * - `getstatus`     -> `status` on the v1 article detail
     * - `kanjis`        -> `kanjis` on the detail, `include_kanjis` on the index
     * - `user/articles` -> `GET v1/articles?author_uid=`, which the dashboard already calls
     */
    public function test_capabilities_without_a_same_shaped_v1_route_are_served_by_the_payload(): void
    {
        $this->assertNotNull(
            $this->matchRoute('GET', 'api/v1/articles/1'),
            'The v1 article detail carries engagement, status and kanjis; it replaces checklike, getstatus and the kanjis route.'
        );

        $this->assertNotNull(
            $this->matchRoute('GET', 'api/v1/articles'),
            'The v1 index accepts author_uid and include_kanjis; it replaces user/articles and the standalone kanjis read.'
        );
    }

    /**
     * Both registrations pointed at methods that do not exist on the v1 ArticleController, so every
     * request raised BadMethodCallException and answered 500 - the `GET testing` situation from
     * RET-AUTH-01, not a stub with behaviour worth keeping. Publicity belongs to
     * `PUT v1/articles/{uuid}`, which takes the desired state instead of toggling, and the
     * authenticated article list belongs to `GET v1/articles?author_uid=`.
     */
    public function test_unimplemented_v1_article_stubs_are_not_registered(): void
    {
        $removed = [
            ['POST', 'api/v1/articles/1/toggle-publicity'],
            ['GET', 'api/v1/user/articles'],
        ];

        foreach ($removed as [$method, $uri]) {
            $this->assertNull(
                $this->matchRoute($method, $uri),
                "Dead v1 registration [{$method} {$uri}] is back; it resolves to no controller method and answers 500."
            );
        }
    }

    /**
     * These share a prefix or a shape with something RET-ART-01 removed and belong to the Catalogue
     * and Post lanes, which this slice does not touch. `api/user/lists` in particular sits one path
     * segment away from the retired `api/user/articles`.
     */
    public function test_sibling_legacy_routes_in_other_domains_are_still_registered(): void
    {
        $retained = [
            ['GET', 'api/user/lists'],
            ['POST', 'api/user/lists/contain'],
            ['POST', 'api/user/list/contain'],
            ['GET', 'api/user/1/lists'],
            ['GET', 'api/lists'],
            ['GET', 'api/list/1'],
            ['POST', 'api/lists/search'],
            ['POST', 'api/list/1/comment'],
            ['POST', 'api/list/1/comment/1/like'],
            ['GET', 'api/posts'],
            ['GET', 'api/post/1'],
            ['POST', 'api/posts/search'],
            ['POST', 'api/post/1/comment'],
            ['GET', 'api/health'],
        ];

        foreach ($retained as [$method, $uri]) {
            $this->assertNotNull(
                $this->matchRoute($method, $uri),
                "Route [{$method} {$uri}] disappeared; RET-ART-01 retires the Article family only."
            );
        }
    }

    public function test_legacy_article_controller_and_its_request_are_gone(): void
    {
        $this->assertFalse(
            class_exists('App\Http\Controllers\ArticleController'),
            'The legacy ArticleController was reintroduced; the v1 Articles controller owns this domain.'
        );

        $this->assertFalse(
            class_exists('App\Http\Requests\Articles\ArticleStoreRequest'),
            'ArticleStoreRequest validated the legacy store route only; v1 uses StoreArticleRequest.'
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
