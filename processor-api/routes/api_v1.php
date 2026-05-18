<?php

use App\Http\v1\Admin\Controllers\UserController as AdminUserController;
use App\Http\v1\Admin\Controllers\UserRoleController as AdminUserRoleController;
use App\Http\v1\Articles\Controllers\ArticleController;
use App\Http\v1\Auth\Controllers\AuthController;
use App\Http\v1\Catalogues\Controllers\CatalogueController;
use App\Http\v1\Comments\Controllers\CommentController;
use App\Http\v1\Engagement\Likes\Controllers\LikeController;
use App\Http\v1\JapaneseMaterial\Kanjis\Controllers\KanjiController;
use App\Http\v1\Users\Controllers\{UserController};
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes - Domain Architecture
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ============================================
    // PUBLIC ROUTES (No Auth Required)
    // ============================================

    // Articles - Public Read Access
    Route::get('articles', [ArticleController::class, 'index']);
    Route::get('articles/{id}', [ArticleController::class, 'show']);
    Route::get('articles/{id}/words', [ArticleController::class, 'words']);

    // Comments - Public Read
    Route::get('articles/{uuid}/comments', [CommentController::class, 'getArticleComments']);
    Route::get('catalogues/{uuid}/comments', [CommentController::class, 'getCatalogueComments']);

    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    // Kanjis
    Route::get('kanjis', [KanjiController::class, 'index']);
    Route::get('kanjis/{identifier}', [KanjiController::class, 'show']);

    // Catalogues - Public Read Access
    Route::get('catalogues', [CatalogueController::class, 'index']);

    // ============================================
    // AUTHENTICATED ROUTES
    // ============================================

    Route::middleware('auth:api')->group(function () {
        // Users - Public Profile
        Route::get('users/{uuid}', [UserController::class, 'show']);
        Route::get('users', [UserController::class, 'index']);

        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);

        // Articles - Authenticated Actions
        Route::post('articles', [ArticleController::class, 'store']);
        Route::put('articles/{uuid}', [ArticleController::class, 'update']);
        Route::delete('articles/{uuid}', [ArticleController::class, 'destroy']);
        Route::get('articles/{uuid}/kanjis-pdf', [ArticleController::class, 'exportKanjisPdf']);
        Route::get('articles/{uuid}/words-pdf', [ArticleController::class, 'exportWordsPdf']);
        Route::post('articles/{id}/toggle-publicity', [ArticleController::class, 'togglePublicity']); // TODO: implement

        // User's Own Articles
        Route::get('user/articles', [ArticleController::class, 'userArticles']); // TODO: implement

        // Catalogues - Authenticated Actions
        Route::post('catalogues', [CatalogueController::class, 'store']);
        Route::get('catalogues/for-item', [CatalogueController::class, 'forItem']);
        Route::post('catalogues/{uuid}/items', [CatalogueController::class, 'addItem']);
        Route::delete('catalogues/{uuid}/items/{item_id}', [CatalogueController::class, 'removeItem']);
        Route::put('catalogues/{uuid}', [CatalogueController::class, 'update']);
        Route::delete('catalogues/{uuid}', [CatalogueController::class, 'destroy']);
        Route::get('catalogues/{uuid}/kanjis-pdf', [CatalogueController::class, 'exportKanjisPdf']);
        Route::get('catalogues/{uuid}/words-pdf', [CatalogueController::class, 'exportWordsPdf']);

        // Comments - Authenticated Write
        Route::post('comments', [CommentController::class, 'store']);

        // Liking - instance agnostic
        Route::post('/like-instance', [LikeController::class, 'likeInstance']);

        // ============================================
        // ADMIN-ONLY ROUTES
        // ============================================

        Route::middleware('checkRole:admin')->group(function () {

            // User Role Management
            Route::get('/admin/roles', [AdminUserRoleController::class, 'getAllRoles']);
            Route::post('/admin/roles', [AdminUserRoleController::class, 'createRole']);
            Route::delete('/admin/roles/{id}', [AdminUserRoleController::class, 'deleteRole']);
            Route::get('/admin/users/{uuid}/roles', [AdminUserRoleController::class, 'getUserRoles']);
            Route::post('/admin/users/{uuid}/roles', [AdminUserRoleController::class, 'assignUserRole']);
            Route::delete('/admin/users/{uuid}/roles', [AdminUserRoleController::class, 'removeUserRole']);

            // User Management
            Route::get('/admin/users', [AdminUserController::class, 'index']);

            // Article Moderation
            Route::post('articles/{id}/status', [ArticleController::class, 'setStatus']); // TODO: implement
            Route::get('articles/pending', [ArticleController::class, 'pending']); // TODO: implement
        });
    });

    Route::get('catalogues/{uuid}', [CatalogueController::class, 'show']);
});
