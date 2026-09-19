<?php

use Illuminate\Support\Facades\Route;

// V1 Routes (Domain Architecture)
require __DIR__.'/api_v1.php';

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Operational endpoint, deliberately retained by RET-AUTH-01: this is the container liveness probe
// (see docs/architecture/deployment-and-runtime.md), not part of the session surface. The
// broadcasting authentication endpoint is the other retained exception, and it lives in
// BroadcastServiceProvider rather than here.
Route::get('health', function () {
    return response()->json(['ok' => true], 200);
});

Route::group([
    // https://medium.com/modulr/create-api-authentication-with-passport-of-laravel-5-6-1dc2d400a7f
    'middleware' => 'auth:api',
], function () {
    // Posts
    Route::post('post', 'PostController@store');
    Route::put('post/{id}', 'PostController@update');
    Route::delete('post/{id}', 'PostController@delete');
    Route::post('post/{id}/like', 'PostController@likePost');
    Route::post('post/{id}/unlike', 'PostController@unlikePost');
    Route::post('post/{id}/checklike', 'PostController@checkIfLikedPost');
    Route::post('post/{id}/toggleLock', 'PostController@toggleLock');
    // Posts Comment
    Route::post('post/{id}/comment', 'PostController@storeComment');
    Route::delete('post/{id}/comment/{commentid}', 'PostController@deleteComment');
    Route::put('post/{id}/comment/{commentid}', 'PostController@updateComment');
    Route::post('post/{id}/comment/{commentid}/like', 'PostController@likeComment');
    Route::post('post/{id}/comment/{commentid}/unlike', 'PostController@unlikeComment');

    // Admin example route
    Route::group(
        [
            'middleware' => 'checkRole:admin',
        ],
        function () {
            Route::post('post/{id}/togglelock', 'PostController@toggleLock');
        }
    );
});

// Authentication - retired, nothing left to register here.
//
// The legacy session routes were retired here (RET-AUTH-01) once every one of them had an exact v1
// replacement and zero routed React callers:
//
//   POST register    -> POST v1/register
//   POST login       -> POST v1/login
//   GET  logout      -> POST v1/logout
//   GET  user        -> GET  v1/me
//   GET  testing     -> nothing; see below
//
// `GET testing` had no replacement because it had no implementation: it pointed at
// UserController@testing, a method that never existed, so every request to it raised
// BadMethodCallException and answered 500. There was no behaviour to preserve.
//
// UserController is gone with these routes - they were its only callers, so the class became
// unreachable. Its default-catalogue side effect on registration now belongs to
// RegisterUserAction, which creates the same four "Known" catalogues inside a transaction.
//
// Retained on purpose and covered by the guarding test: `health` above, and the broadcasting
// authentication route that BroadcastServiceProvider registers under this same `api` prefix.
// Neither is a session endpoint. The `user/...` routes elsewhere in this file are also unaffected -
// only the exact `user` path was retired.
//
// Guarded by tests/Feature/Routes/LegacyAuthRouteRetirementTest.php, with the v1 contract itself
// pinned by tests/Feature/Auth/AuthV1Test.php.

// Articles - retired, nothing left to register here.
//
// The whole legacy Article family was retired here (RET-ART-01) once every route had a v1
// replacement and zero routed React callers:
//
//   articles, article/{id}                -> GET  v1/articles, GET v1/articles/{id}
//   articles/search                       -> GET  v1/articles query filters (AFM-01..AFM-07)
//   article/{id}/words                    -> GET  v1/articles/{id}/words
//   article (POST), article/{id} (PUT)    -> POST v1/articles, PUT v1/articles/{uuid}
//   article/{id} (DELETE)                 -> DELETE v1/articles/{uuid}
//   article/{id}/kanjis-pdf|words-pdf     -> GET  v1/articles/{uuid}/kanjis-pdf|words-pdf
//   article/{id}/like|unlike              -> POST v1/like-instance, one idempotent toggle
//   article/{id}/comment...               -> POST/PUT/DELETE v1/comments
//   comment like|unlike                   -> POST v1/like-instance
//   article/{id}/setstatus                -> POST v1/articles/{uuid}/status
//   articles/pendinglist                  -> GET  v1/articles/pending
//
// Four had no same-shaped v1 endpoint because the answer is already on a payload the caller
// fetches anyway, not because the migration is unfinished:
//
//   article/{id}/checklike -> `engagement` on the v1 article detail
//   article/{id}/getstatus -> `status` on the v1 article detail
//   article/{id}/kanjis    -> `kanjis` on the detail, `include_kanjis` on the index
//   user/articles          -> GET v1/articles?author_uid=, which the dashboard already calls
//
// `article/{id}/togglepublicity` is replaced by PUT v1/articles/{uuid}, which takes the desired
// state instead of toggling - the same correction the post lock route made. That also drops a
// guard that never worked: the legacy check rejected when the caller was not the owner OR not an
// admin, so an ordinary owner could never reach their own article.
//
// ArticleController is gone with these routes - they were its only callers, so the class became
// unreachable, and App\Http\Requests\Articles\ArticleStoreRequest went with its one route. The
// global impression and hashtag helpers stay: the Catalogue and Post controllers still route
// through them. So do the per-class `sortByViewsTotal` and `incrementDownload` copies on those
// two controllers, which were never shared with this one.
//
// Guarded by tests/Feature/Routes/LegacyArticleRouteRetirementTest.php.

// Japanese Resources - retired, nothing left to register here.
//
// The public Kanji/Radical/Word/Sentence read, search and relation routes were
// retired here (RET-JPN-READ-01) once every one of them had an exact v1
// replacement and zero routed React callers:
//
//   kanjis, kanji/{kanji}            -> GET v1/kanjis, GET v1/kanjis/{identifier}
//   kanjis/search                    -> GET v1/kanjis query filters
//   radicals, radical/{radical}      -> GET v1/radicals, GET v1/radicals/{identifier}
//   radicals/search                  -> GET v1/radicals query filters
//   words, word/{id}                 -> GET v1/words, GET v1/words/{identifier}
//   word/{id}/kanjis                 -> `kanjis` on the v1 word detail payload
//   words/search                     -> GET v1/words query filters
//   sentences, sentence/{id}         -> GET v1/sentences, GET v1/sentences/{identifier}
//   sentence/{id}/kanjis|words       -> `kanjis`/`words` on the v1 sentence detail payload
//   sentences/search                 -> GET v1/sentences query filters
//
// Guarded by tests/Feature/Routes/LegacyJapaneseReadRouteRetirementTest.php.
//
// The Sentence writes and the Sentence comment endpoints that shared the controller
// with those reads were retired here (RET-SEN-01) once every one of them had an exact
// v1 replacement and zero routed React callers:
//
//   sentence (POST), sentence/{id} (PUT)  -> POST v1/sentences, PUT v1/sentences/{uuid}
//   sentence/{id} (DELETE)                -> DELETE v1/sentences/{uuid}
//   sentence/{id}/comment...              -> POST/PUT/DELETE v1/comments
//   comment like|unlike                   -> POST v1/like-instance, one idempotent toggle
//
// Two things the legacy writes did are recorded here so nobody goes looking for them
// in v1:
//
// - `POST sentence` and `PUT sentence/{id}` never completed. getWordIdsFromText was
//   pasted from the Article helper and still read `$article->content`, a variable that
//   did not exist in the method, so every call raised ErrorException after the kanji
//   pass. Word attachment through this controller never ran; v1 SentenceController
//   synchronises both relations inside one transaction.
// - `PUT sentence/{id}` and `DELETE sentence/{id}` did `Sentence::find($id)` with no
//   owner, admin or imported check, so any signed-in user could rewrite or delete any
//   sentence, imported ones included. v1 restricts writes to the owner or an admin and
//   keeps imported sentences immutable for everyone. That is a correction, not a
//   behaviour to preserve.
//
// JapaneseDataController is gone with these routes - they were its last callers, so
// the class became unreachable. Its mb_str_split/getKanjiIdsFromText/getWordIdsFromText
// copies went with it; the same-named global functions in app/Helpers are Article-typed
// and still serve the seeders.
//
// Guarded by tests/Feature/Routes/LegacySentenceRouteRetirementTest.php, with the v1
// contract itself pinned by tests/Feature/JapaneseMaterial/Sentences and
// tests/Feature/Comments.

// Custom Lists - retired, nothing left to register here.
//
// The whole legacy List family was retired here (RET-CAT-01) once every route had a v1
// replacement and zero routed React callers:
//
//   lists, list/{id}                       -> GET  v1/catalogues, GET v1/catalogues/{uuid}
//   lists/search                           -> GET  v1/catalogues query filters
//   list (POST), list/{id} (PUT)           -> POST v1/catalogues, PUT v1/catalogues/{uuid}
//   list/{id} (DELETE)                     -> DELETE v1/catalogues/{uuid}
//   user/lists/contain                     -> GET  v1/catalogues/for-item
//   list/{id}/additem, additemwhileaway    -> POST v1/catalogues/{uuid}/items
//   list/{id}/removeitem, removeitem...    -> DELETE v1/catalogues/{uuid}/items/{item_id}
//   list/{id}/kanjis-pdf|words-pdf         -> GET  v1/catalogues/{uuid}/kanjis-pdf|words-pdf
//   list/{id}/like|unlike                  -> POST v1/like-instance, one idempotent toggle
//   list/{id}/comment...                   -> POST/PUT/DELETE v1/comments
//   comment like|unlike                    -> POST v1/like-instance
//
// The `whileaway` pair is not a separate capability: `user/list/additemwhileaway` and
// `user/list/removeitemwhileaway` are `list/{id}/additem` and `list/{id}/removeitem` with the list
// id moved from the path into the body, and `v1/catalogues/{uuid}/items` answers all four.
//
// Three had no same-shaped v1 endpoint because the answer is already on a payload the caller
// fetches anyway, not because the migration is unfinished:
//
//   list/{id}/checklike              -> `engagement` on the v1 catalogue detail
//   user/lists, user/{id}/lists      -> GET v1/catalogues?owner_uid=
//   lists/search                     -> the v1 index filters: search, type, sort_by, public_only
//
// `list/{id}/togglepublicity` is replaced by PUT v1/catalogues/{uuid}, which takes the desired
// state instead of toggling - the same correction the article and post routes made.
//
// `list/{id}/radicals-pdf` and `list/{id}/sentences-pdf` are the one capability that goes without
// a v1 replacement. CAT-CLEAN-FE-01 had already dropped the frontend half - the catalogue detail
// offers the download for kanji and word catalogues only - so these two routes had no caller
// either, and CataloguePdfExportService supports exactly those two kinds. Recreating them as v1
// service-backed exports is CAT-PDF-01 (#303), not a loose end of this slice.
//
// `user/list/contain` is removed as dead code rather than retired, the `GET testing` situation
// from RET-AUTH-01: JapaneseDataController@getUserListAndCheckIfListHasItem returned the caller's
// own `objects` payload before reaching the list lookup and the `isLearned` flagging below it, so
// there was no behaviour to preserve. checkIfBelongToList, its only caller, went with it.
//
// CustomListController is gone with these routes - they were its only callers, so the class became
// unreachable, and App\Http\Requests\CustomListStoreRequest went with its one route. The
// numeric browser URLs stay: /lists, /list/:catalogueId, /newlist and /list/edit/:catalogueId are
// React Router paths, and they reach canonical UUID routes through GET v1/catalogues/legacy/{id}.
//
// Guarded by tests/Feature/Routes/LegacyCatalogueRouteRetirementTest.php, with the v1 contract
// itself pinned by the tests under tests/Feature/Catalogues.

// Posts
Route::get('posts', 'PostController@index');
Route::get('post/{id}', 'PostController@show');
Route::post('posts/search', 'PostController@generateQuery');
