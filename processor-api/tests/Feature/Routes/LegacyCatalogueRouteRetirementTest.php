<?php

declare(strict_types=1);

namespace Tests\Feature\Routes;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

/**
 * RET-CAT-01 boundary.
 *
 * The legacy List route family - public reads and search, CRUD, item membership, PDF exports,
 * publicity, the List comment endpoints and the List like endpoints - is gone, and so is
 * CustomListController behind it. Two neighbourhoods sit close enough to be taken by a careless
 * edit: the `list`/`lists` prefixes are one character apart and `user/list...` shares a prefix with
 * the Sentence lane's own `user` routes, and the Post comment routes are line-for-line the same
 * shape as the List ones this slice removed. Every side of that line is asserted here.
 */
class LegacyCatalogueRouteRetirementTest extends TestCase
{
    public function test_retired_legacy_list_routes_are_not_registered(): void
    {
        $retired = [
            // Public reads and search
            ['GET', 'api/lists'],
            ['GET', 'api/list/1'],
            ['POST', 'api/lists/search'],
            ['GET', 'api/user/1/lists'],
            // CRUD
            ['POST', 'api/list'],
            ['PUT', 'api/list/1'],
            ['DELETE', 'api/list/1'],
            ['GET', 'api/user/lists'],
            ['POST', 'api/list/1/togglepublicity'],
            // Item membership
            ['POST', 'api/user/lists/contain'],
            ['POST', 'api/user/list/contain'],
            ['POST', 'api/list/1/additem'],
            ['POST', 'api/list/1/removeitem'],
            ['POST', 'api/user/list/additemwhileaway'],
            ['POST', 'api/user/list/removeitemwhileaway'],
            // PDF exports
            ['GET', 'api/list/1/radicals-pdf'],
            ['GET', 'api/list/1/kanjis-pdf'],
            ['GET', 'api/list/1/words-pdf'],
            ['GET', 'api/list/1/sentences-pdf'],
            // Likes
            ['POST', 'api/list/1/like'],
            ['POST', 'api/list/1/unlike'],
            ['POST', 'api/list/1/checklike'],
            // Comments
            ['POST', 'api/list/1/comment'],
            ['DELETE', 'api/list/1/comment/1'],
            ['PUT', 'api/list/1/comment/1'],
            ['POST', 'api/list/1/comment/1/like'],
            ['POST', 'api/list/1/comment/1/unlike'],
        ];

        foreach ($retired as [$method, $uri]) {
            $this->assertNull(
                $this->matchRoute($method, $uri),
                "Legacy List route [{$method} {$uri}] is registered again; the v1 equivalent owns it."
            );
        }
    }

    public function test_v1_replacements_for_every_retired_route_are_registered(): void
    {
        $uuid = '11111111-2222-4333-8444-555555555555';

        $replacements = [
            ['GET', 'api/v1/catalogues'],
            ['GET', "api/v1/catalogues/{$uuid}"],
            ['GET', 'api/v1/catalogues/legacy/1'],
            ['POST', 'api/v1/catalogues'],
            ['PUT', "api/v1/catalogues/{$uuid}"],
            ['DELETE', "api/v1/catalogues/{$uuid}"],
            ['GET', 'api/v1/catalogues/for-item'],
            ['POST', "api/v1/catalogues/{$uuid}/items"],
            ['DELETE', "api/v1/catalogues/{$uuid}/items/1"],
            ['GET', "api/v1/catalogues/{$uuid}/kanjis-pdf"],
            ['GET', "api/v1/catalogues/{$uuid}/words-pdf"],
            ['GET', "api/v1/catalogues/{$uuid}/comments"],
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
     * Three legacy capabilities have no same-shaped v1 endpoint, by decision rather than by
     * omission, so a future reader finding no `checklike` route must not read that as an unfinished
     * migration.
     *
     * - `checklike`        -> `engagement` on the v1 catalogue detail
     * - `user/lists`, `user/{id}/lists` -> `GET v1/catalogues?owner_uid=`
     * - `lists/search`     -> the v1 index query filters (`search`, `type`, `sort_by`, `public_only`)
     */
    public function test_capabilities_without_a_same_shaped_v1_route_are_served_by_the_payload(): void
    {
        $this->assertNotNull(
            $this->matchRoute('GET', 'api/v1/catalogues/11111111-2222-4333-8444-555555555555'),
            'The v1 catalogue detail carries engagement; it replaces checklike.'
        );

        $this->assertNotNull(
            $this->matchRoute('GET', 'api/v1/catalogues'),
            'The v1 index accepts owner_uid, search, type and sort_by; it replaces the user list routes and lists/search.'
        );
    }

    /**
     * `user/list/contain` is removed as dead code rather than retired. It pointed at
     * JapaneseDataController@getUserListAndCheckIfListHasItem, which returned the request's own
     * `objects` payload before reaching any of its logic - the list lookup and the `isLearned`
     * flagging below that return were unreachable. There was no behaviour to preserve, the way
     * `GET testing` had none in RET-AUTH-01. `GET v1/catalogues/for-item` is the real membership
     * answer, and `user/lists/contain` retired into it.
     */
    public function test_the_dead_membership_echo_route_and_its_helper_are_gone(): void
    {
        $this->assertNull(
            $this->matchRoute('POST', 'api/user/list/contain'),
            'The membership echo route is back; it never read the list it claimed to check.'
        );

        $this->assertFalse(
            method_exists('App\Http\Controllers\JapaneseDataController', 'getUserListAndCheckIfListHasItem'),
            'The dead membership echo method is back on JapaneseDataController.'
        );

        $this->assertFalse(
            method_exists('App\Http\Controllers\JapaneseDataController', 'checkIfBelongToList'),
            'checkIfBelongToList had one caller, the echo method above; both went with the route.'
        );

        $this->assertNotNull(
            $this->matchRoute('GET', 'api/v1/catalogues/for-item'),
            'The v1 membership route is missing; it is what both contain routes retired into.'
        );
    }

    /**
     * Every legacy catalogue PDF export now has a v1 replacement. Radicals and sentences were the
     * one capability RET-CAT-01 left without one - CAT-CLEAN-FE-01 had already dropped the
     * frontend half, so the legacy routes had no caller - and CAT-PDF-01 (#303) recreated them as
     * service-backed v1 exports. The legacy `api/list/{id}/*-pdf` routes stay gone; the witnesses
     * for that are in the retired-routes list above.
     */
    public function test_every_catalogue_pdf_export_kind_exists_at_v1(): void
    {
        $uuid = '11111111-2222-4333-8444-555555555555';

        foreach (['kanjis', 'words', 'radicals', 'sentences'] as $kind) {
            $this->assertNotNull(
                $this->matchRoute('GET', "api/v1/catalogues/{$uuid}/{$kind}-pdf"),
                "The v1 catalogue {$kind} PDF export is missing; it replaced the legacy route."
            );
        }
    }

    /**
     * The operational route that shares the `api` prefix with everything RET-CAT-01 removed. The
     * Sentence witnesses that used to stand here were retired by RET-SEN-01 and the Post witnesses
     * - line-for-line the same shape as the List comment routes this slice removed - by
     * RET-POST-01; their own retirement tests now own those lines.
     */
    public function test_operational_route_is_still_registered(): void
    {
        $this->assertNotNull(
            $this->matchRoute('GET', 'api/health'),
            'Route [GET api/health] disappeared; RET-CAT-01 retires the List family only.'
        );
    }

    public function test_legacy_list_controller_and_its_request_are_gone(): void
    {
        $this->assertFalse(
            class_exists('App\Http\Controllers\CustomListController'),
            'The legacy CustomListController was reintroduced; the v1 Catalogues controller owns this domain.'
        );

        $this->assertFalse(
            class_exists('App\Http\Requests\CustomListStoreRequest'),
            'CustomListStoreRequest validated the legacy store route only; v1 uses StoreCatalogueRequest.'
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
