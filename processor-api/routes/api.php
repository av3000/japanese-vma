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
    // Articles CUD
    Route::post('article', 'ArticleController@store');
    Route::put('article/{id}', 'ArticleController@update');
    Route::delete('article/{id}', 'ArticleController@delete');
    Route::get('user/articles', 'ArticleController@getUserArticles');
    Route::post('article/{id}/like', 'ArticleController@likeArticle');
    Route::post('article/{id}/unlike', 'ArticleController@unlikeArticle');
    Route::post('article/{id}/checklike', 'ArticleController@checkIfLikedArticle');
    Route::get('article/{id}/kanjis-pdf', 'ArticleController@generateKanjisPdf');
    Route::get('article/{id}/words-pdf', 'ArticleController@generateWordsPdf');
    Route::post('article/{id}/togglepublicity', 'ArticleController@togglePublicity');
    // Article Comment
    Route::post('article/{id}/comment', 'ArticleController@storeComment');
    Route::delete('article/comment/{commentid}', 'ArticleController@deleteComment');
    Route::put('article/{id}/comment/{commentid}', 'ArticleController@updateComment');
    Route::post('article/{id}/comment/{commentid}/like', 'ArticleController@likeComment');
    Route::post('article/{id}/comment/{commentid}/unlike', 'ArticleController@unlikeComment');
    // Route::post('article/{id}/comment/{commentid}/checklike', 'ArticleController@checkIfLikedComment');

    // Lists CUD
    Route::post('list', 'CustomListController@store');
    Route::put('list/{id}', 'CustomListController@update');
    Route::delete('list/{id}', 'CustomListController@delete');
    Route::get('user/lists', 'CustomListController@getUserLists');
    Route::post('user/lists/contain', 'CustomListController@getUserListsForElementsToAdd');
    Route::post('user/list/contain', 'JapaneseDataController@getUserListAndCheckIfListHasItem');
    Route::post('list/{id}/removeitem', 'CustomListController@removeFromList');
    Route::post('user/list/removeitemwhileaway', 'CustomListController@removeFromListWhileAway');
    Route::post('user/list/additemwhileaway', 'CustomListController@addToListWhileAway');
    Route::post('list/{id}/additem', 'CustomListController@addToList');
    Route::post('list/{id}/like', 'CustomListController@likeList');
    Route::post('list/{id}/unlike', 'CustomListController@unlikeList');
    Route::post('list/{id}/checklike', 'CustomListController@checkIfLikedList');
    Route::get('list/{id}/radicals-pdf', 'CustomListController@generateRadicalsPdf');
    Route::get('list/{id}/kanjis-pdf', 'CustomListController@generateKanjisPdf');
    Route::get('list/{id}/words-pdf', 'CustomListController@generateWordsPdf');
    Route::get('list/{id}/sentences-pdf', 'CustomListController@generateSentencesPdf');
    Route::post('list/{id}/togglepublicity', 'CustomListController@togglePublicity');

    // List Comment
    Route::post('list/{id}/comment', 'CustomListController@storeComment');
    Route::delete('list/{id}/comment/{commentid}', 'CustomListController@deleteComment');
    Route::put('list/{id}/comment/{commentid}', 'CustomListController@updateComment');
    Route::post('list/{id}/comment/{commentid}/like', 'CustomListController@likeComment');
    Route::post('list/{id}/comment/{commentid}/unlike', 'CustomListController@unlikeComment');

    // Sentences CUD
    Route::post('sentence', 'JapaneseDataController@storeSentence');
    Route::put('sentence/{id}', 'JapaneseDataController@updateSentence');
    Route::delete('sentence/{id}', 'JapaneseDataController@deleteSentence');
    // Sentences Comment
    Route::post('sentence/{id}/comment', 'JapaneseDataController@storeComment');
    Route::delete('sentence/{id}/comment/{commentid}', 'JapaneseDataController@deleteComment');
    Route::put('sentence/{id}/comment/{commentid}', 'JapaneseDataController@updateComment');
    Route::post('sentence/{id}/comment/{commentid}/like', 'JapaneseDataController@likeComment');
    Route::post('sentence/{id}/comment/{commentid}/unlike', 'JapaneseDataController@unlikeComment');

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
            Route::post('article/{id}/setstatus', 'ArticleController@setStatus');
            Route::get('article/{id}/getstatus', 'ArticleController@getStatus');
            Route::get('articles/pendinglist', 'ArticleController@getArticlesPending');
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

// Articles
Route::get('articles', 'ArticleController@index');
Route::get('article/{id}', 'ArticleController@show');
Route::get('article/{id}/kanjis', 'ArticleController@articleKanjis');
Route::get('article/{id}/words', 'ArticleController@articleWords');
Route::post('articles/search', 'ArticleController@generateQuery');

// Japanese Resources - public reads retired, nothing left to register here.
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
// JapaneseDataController is still routed for the writes it has no v1 owner for
// yet - Sentence store/update/delete and the Sentence comment endpoints above
// (both retired by RET-SEN-01) and `user/list/contain`, which belongs to the
// Catalogue lane. Those keep mb_str_split/getKanjiIdsFromText/getWordIdsFromText
// and checkIfBelongToList alive in the controller; the read-only helpers went
// with the routes.

// Custom Lists
Route::get('lists', 'CustomListController@index');
Route::get('list/{id}', 'CustomListController@show');
Route::post('lists/search', 'CustomListController@generateQuery');
Route::get('user/{id}/lists', 'CustomListController@getUserLists');

// Posts
Route::get('posts', 'PostController@index');
Route::get('post/{id}', 'PostController@show');
Route::post('posts/search', 'PostController@generateQuery');
