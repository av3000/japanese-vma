<?php

declare(strict_types=1);

namespace Tests\Feature\Routes;

use App\Http\Controllers\JapaneseDataController;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

/**
 * RET-JPN-READ-01 boundary.
 *
 * The legacy public Kanji/Radical/Word/Sentence read, search and relation
 * routes are gone; the Sentence writes and Sentence comment routes that share
 * JapaneseDataController with them are not. This test guards both halves -
 * re-registering a read should fail, and so should collateral damage to the
 * writes while RET-SEN-01 is still open.
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

    public function test_retained_legacy_japanese_write_routes_are_still_registered(): void
    {
        $retained = [
            ['POST', 'api/sentence'],
            ['PUT', 'api/sentence/1'],
            ['DELETE', 'api/sentence/1'],
            ['POST', 'api/sentence/1/comment'],
            ['PUT', 'api/sentence/1/comment/2'],
            ['DELETE', 'api/sentence/1/comment/2'],
            ['POST', 'api/sentence/1/comment/2/like'],
            ['POST', 'api/sentence/1/comment/2/unlike'],
            ['POST', 'api/user/list/contain'],
        ];

        foreach ($retained as [$method, $uri]) {
            $this->assertNotNull(
                $this->matchRoute($method, $uri),
                "Legacy route [{$method} {$uri}] disappeared; it has no v1 replacement yet and must survive RET-JPN-READ-01."
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

    public function test_removed_read_methods_are_gone_from_the_legacy_controller(): void
    {
        $removed = [
            'indexKanjis', 'showKanji', 'generateKanjisQuery',
            'indexRadicals', 'showRadical', 'generateRadicalsQuery',
            'indexWords', 'showWord', 'wordKanjis', 'generateWordsQuery',
            'indexSentences', 'showSentence', 'sentenceKanjis', 'sentenceWords', 'generateSentencesQuery',
        ];

        foreach ($removed as $method) {
            $this->assertFalse(
                method_exists(JapaneseDataController::class, $method),
                "JapaneseDataController::{$method}() was reintroduced; the v1 controllers own this read."
            );
        }
    }

    public function test_helpers_the_retained_writes_depend_on_survive(): void
    {
        $kept = ['mb_str_split', 'getKanjiIdsFromText', 'getWordIdsFromText', 'checkIfBelongToList'];

        foreach ($kept as $method) {
            $this->assertTrue(
                method_exists(JapaneseDataController::class, $method),
                "JapaneseDataController::{$method}() is still called by the retained Sentence writes."
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
