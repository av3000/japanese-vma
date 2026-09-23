<?php

declare(strict_types=1);

namespace Tests\Feature\Routes;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

/**
 * RET-JPN-READ-01 boundary.
 *
 * The legacy public Kanji/Radical/Word/Sentence read, search and relation
 * routes are gone. While RET-SEN-01 was still open this test also guarded the
 * Sentence writes that shared JapaneseDataController with them; those have
 * since retired too, and LegacySentenceRouteRetirementTest owns that line.
 */
class LegacyJapaneseReadRouteRetirementTest extends TestCase
{
    public function test_retired_legacy_japanese_read_routes_are_not_registered(): void
    {
        $retired = [
            ['GET', 'api/kanjis'],
            ['GET', 'api/kanji/1'],
            ['POST', 'api/kanjis/search'],
            ['GET', 'api/radicals'],
            ['GET', 'api/radical/1'],
            ['POST', 'api/radicals/search'],
            ['GET', 'api/words'],
            ['GET', 'api/word/1'],
            ['GET', 'api/word/1/kanjis'],
            ['POST', 'api/words/search'],
            ['GET', 'api/sentences'],
            ['GET', 'api/sentence/1'],
            ['GET', 'api/sentence/1/kanjis'],
            ['GET', 'api/sentence/1/words'],
            ['POST', 'api/sentences/search'],
        ];

        foreach ($retired as [$method, $uri]) {
            $this->assertNull(
                $this->matchRoute($method, $uri),
                "Legacy Japanese read route [{$method} {$uri}] is registered again; the v1 equivalent owns it."
            );
        }
    }

    public function test_v1_replacements_for_every_retired_read_are_registered(): void
    {
        $replacements = [
            ['GET', 'api/v1/kanjis'],
            ['GET', 'api/v1/kanjis/1'],
            ['GET', 'api/v1/radicals'],
            ['GET', 'api/v1/radicals/1'],
            ['GET', 'api/v1/words'],
            ['GET', 'api/v1/words/1'],
            ['GET', 'api/v1/sentences'],
            ['GET', 'api/v1/sentences/1'],
        ];

        foreach ($replacements as [$method, $uri]) {
            $this->assertNotNull(
                $this->matchRoute($method, $uri),
                "v1 replacement [{$method} {$uri}] is missing; the legacy read it replaced is already gone."
            );
        }
    }

    /**
     * The read methods went with their routes in RET-JPN-READ-01 and the class itself went with
     * the Sentence writes in RET-SEN-01. Asserting on the class keeps this test meaningful even
     * if someone reintroduces the controller for an unrelated reason.
     */
    public function test_the_legacy_japanese_controller_is_gone(): void
    {
        $this->assertFalse(
            class_exists('App\Http\Controllers\JapaneseDataController'),
            'JapaneseDataController was reintroduced; the v1 JapaneseMaterial controllers own every read and write it served.'
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
