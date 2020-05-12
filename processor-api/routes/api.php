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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group([
	// https://medium.com/modulr/create-api-authentication-with-passport-of-laravel-5-6-1dc2d400a7f
      'middleware' => 'auth:api'
    ], function() {
        Route::get('logout', 'UserController@logout');
        Route::get('user', 'UserController@user');
		// Articles CUD
		Route::post('article', 'ArticleController@store');
		Route::post('article/{id}/like', 'ArticleController@likeArticle');
		Route::post('article/{id}/unlike', 'ArticleController@unlikeArticle');
		Route::put('article/{id}', 'ArticleController@update');
		Route::delete('article/{id}', 'ArticleController@delete');
		
		// Sentences CUD
		Route::post('sentence', 'JapaneseDataController@storeSentence');
		Route::put('sentence/{id}', 'JapaneseDataController@updateSentence');
		Route::delete('sentence/{id}', 'JapaneseDataController@deleteSentence');

		// Admin example route
		Route::group([
			'middleware'=> 'checkRole:admin'
			], function() {
				Route::get('articles', 'ArticleController@index');
			}
		);
	});
	
// Authentication routes
Route::post('/register', 'UserController@register');
Route::post('/login', 'UserController@login');

// Articles
Route::get('article/{id}', 'ArticleController@show');
Route::get('article/{id}/kanjis', 'ArticleController@articleKanjis');
Route::get('article/{id}/words', 'ArticleController@articleWords');
Route::get('articles/search', 'ArticleController@generateQuery');

// Japanese Resources
Route::get('kanjis', 'JapaneseDataController@indexKanjis');
Route::get('kanji/{kanji}', 'JapaneseDataController@showKanji');
Route::get('radicals', 'JapaneseDataController@indexRadicals');
Route::get('radical/{radical}', 'JapaneseDataController@showRadical');
Route::get('words', 'JapaneseDataController@indexWords');
Route::get('word/{id}', 'JapaneseDataController@showWord');
Route::get('word/{id}/kanjis', 'JapaneseDataController@wordKanjis');
Route::get('sentences', 'JapaneseDataController@indexSentences');
Route::get('sentences/{id}', 'JapaneseDataController@showSentence');
Route::get('sentences/{id}/kanjis', 'JapaneseDataController@sentenceKanjis');
Route::get('sentences/{id}/words', 'JapaneseDataController@sentenceWords');
Route::get('material/search', 'JapaneseDataController@generateQuery');
