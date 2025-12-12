<?php

use Illuminate\Support\Facades\Route;
use App\Http\v1\Articles\Controllers\ArticleController;
use App\Http\v1\Auth\Controllers\AuthController;
use App\Http\v1\Comments\Controllers\CommentController;
use App\Http\v1\Users\Controllers\{UserController};
use App\Http\v1\Admin\Controllers\{UserRoleController as AdminUserRoleController, UserController as AdminUserController};

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
    Route::get('articles/{id}/kanjis', [ArticleController::class, 'kanjis']);
    Route::get('articles/{id}/words', [ArticleController::class, 'words']);

    // Comments - Public Read
    Route::get('articles/{uuid}/comments', [CommentController::class, 'getArticleComments']);

    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

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
        Route::post('articles/{id}/like', [ArticleController::class, 'like']); // TODO: implement
        Route::delete('articles/{id}/like', [ArticleController::class, 'unlike']); // TODO: implement
        Route::post('articles/{id}/toggle-publicity', [ArticleController::class, 'togglePublicity']); // TODO: implement

        // User's Own Articles
        Route::get('user/articles', [ArticleController::class, 'userArticles']); // TODO: implement

        // Comments - Authenticated Write
        Route::post('articles/{uuid}/comments', [CommentController::class, 'store']);


        // ============================================
        // ADMIN-ONLY ROUTES
        // ============================================

        Route::middleware('checkRole:admin')->group(function () {

            // User Role Management
            Route::get('/admin/roles', [AdminUserRoleController::class, 'getAllRoles']);
            Route::get('/admin/users/{uuid}/roles', [AdminUserRoleController::class, 'getUserRoles']);
            Route::post('/admin/users/{uuid}/roles', [AdminUserRoleController::class, 'assignRole']);
            Route::delete('/admin/users/{uuid}/roles/{role}', [AdminUserRoleController::class, 'removeRole']);

            // User Management
            Route::get('/admin/users', [AdminUserController::class, 'index']);

            // Article Moderation
            Route::post('articles/{id}/status', [ArticleController::class, 'setStatus']); // TODO: implement
            Route::get('articles/pending', [ArticleController::class, 'pending']); // TODO: implement
        });
    });
});
