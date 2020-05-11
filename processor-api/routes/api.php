<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


// Authentication
Route::post('register', 'UserController@register');
Route::post('login', 'UserController@login');
Route::get('profile', 'UserController@getAuthenticatedUser');

Route::middleware('auth:api')->get('/user', function(Request $request) {
    return $request->user();
});

// Articles
Route::get('articles', 'ArticleController@index');
Route::get('article/{id}', 'ArticleController@show');
Route::post('article', 'ArticleController@store');
Route::put('article/{id}', 'ArticleController@update');
Route::delete('article/{id}', 'ArticleController@delete');

Route::get('article/{id}/kanjis', 'ArticleController@fetchKanjis');
Route::get('article/{id}/words', 'ArticleController@fetchWords');

Route::get('articles/search', 'ArticleController@generateQuery');

// Specific User profile
// Route::get('profile/{id}', 'TextController@getUserTexts');

// Texts
// Route::get('texts', 'TextController@index');
// Route::get('text/{id}', 'TextController@show');
// Route::post('text', 'TextController@store');
// Route::put('text/{id}', 'TextController@update');
// Route::delete('text/{id}', 'TextController@delete');

// Route::get('text/{id}/kanjis', 'TextController@fetchKanjis');
// Route::get('text/{id}/words', 'TextController@fetchWords');

// Route::get('texts/search', 'TextController@generateQuery');

// Route::fallback(function(){
//     return response()->json([
//         'message' => 'Page Not Found. If error persists, contact administration.'], 404);
// });