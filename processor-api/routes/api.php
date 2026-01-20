<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// V1 Routes (Domain Architecture)
require __DIR__ . '/api_v1.php';

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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group([
    // https://medium.com/modulr/create-api-authentication-with-passport-of-laravel-5-6-1dc2d400a7f
    'middleware' => 'auth:api'
], function () {
    Route::get('logout', 'UserController@logout');
    Route::get('user', 'UserController@user');

    // TODO: move to V1 with dedicated controller for liking abstracted logic.
    // Logic is ready, would only require to rename the API route
    Route::post('article/like', 'ArticleController@likeInstance');

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
            'middleware' => 'checkRole:admin'
        ],
        function () {
            Route::post('article/{id}/setstatus', 'ArticleController@setStatus');
            Route::get('article/{id}/getstatus', 'ArticleController@getStatus');
            Route::get('articles/pendinglist', 'ArticleController@getArticlesPending');
            Route::post('post/{id}/togglelock', 'PostController@toggleLock');
        }
    );
});

// Authentication routes
Route::post('/register', 'UserController@register');
Route::post('/login', 'UserController@login');
Route::get('/testing', 'UserController@testing');

// Articles
Route::get('articles', 'ArticleController@index');
Route::get('article/{id}', 'ArticleController@show');
Route::get('article/{id}/kanjis', 'ArticleController@articleKanjis');
Route::get('article/{id}/words', 'ArticleController@articleWords');
Route::post('articles/search', 'ArticleController@generateQuery');

// Japanese Resources
Route::get('kanjis', 'JapaneseDataController@indexKanjis');
Route::get('kanji/{kanji}', 'JapaneseDataController@showKanji');
Route::post('kanjis/search', 'JapaneseDataController@generateKanjisQuery');
Route::get('radicals', 'JapaneseDataController@indexRadicals');
Route::get('radical/{radical}', 'JapaneseDataController@showRadical');
Route::post('radicals/search', 'JapaneseDataController@generateRadicalsQuery');
Route::get('words', 'JapaneseDataController@indexWords');
Route::get('word/{id}', 'JapaneseDataController@showWord');
Route::get('word/{id}/kanjis', 'JapaneseDataController@wordKanjis');
Route::post('words/search', 'JapaneseDataController@generateWordsQuery');
Route::get('sentences', 'JapaneseDataController@indexSentences');
Route::get('sentence/{id}', 'JapaneseDataController@showSentence');
Route::get('sentence/{id}/kanjis', 'JapaneseDataController@sentenceKanjis');
Route::get('sentence/{id}/words', 'JapaneseDataController@sentenceWords');
Route::post('sentences/search', 'JapaneseDataController@generateSentencesQuery');

// Custom Lists
Route::get('lists', 'CustomListController@index');
Route::get('list/{id}', 'CustomListController@show');
Route::post('lists/search', 'CustomListController@generateQuery');
Route::get('user/{id}/lists', 'CustomListController@getUserLists');

// Posts
Route::get('posts', 'PostController@index');
Route::get('post/{id}', 'PostController@show');
Route::post('posts/search', 'PostController@generateQuery');
