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

		Route::post('article', 'ArticleController@store');
		Route::put('article/{id}', 'ArticleController@update');
		Route::delete('article/{id}', 'ArticleController@delete');

		Route::group([
			'middleware'=> 'checkRole:admin'
			], function() {
				Route::get('articles', 'ArticleController@index');
			}
		);
    });

Route::post('/register', 'UserController@register');
Route::post('/login', 'UserController@login');

// Articles
Route::get('article/{id}', 'ArticleController@show');
Route::get('article/{id}/kanjis', 'ArticleController@articleKanjis');
Route::get('article/{id}/words', 'ArticleController@farticleWords');

Route::get('articles/search', 'ArticleController@generateQuery');
