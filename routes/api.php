<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CommentController;
use App\Http\Controllers\API\FollowController;
use App\Http\Controllers\API\PostController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\UserProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute Autentikasi Publik
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Rute Terproteksi (Memerlukan Token Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // --- Autentikasi ---
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // --- Manajemen Profil Sendiri ---
    Route::post('/profile',        [UserController::class, 'updateProfile']);
    Route::delete('/profile/photo',[UserController::class, 'removeProfilePhoto']);

    // --- Profil Pengguna Lain ---
    Route::get('/users/{username}',             [UserProfileController::class, 'show']);
    Route::get('/users/{username}/followers',   [FollowController::class, 'followers']);
    Route::get('/users/{username}/following',   [FollowController::class, 'following']);
    Route::post('/users/{username}/follow',     [FollowController::class, 'follow']);
    Route::delete('/users/{username}/unfollow', [FollowController::class, 'unfollow']);

    // --- Postingan ---
    Route::get('/posts',          [PostController::class, 'index']);
    Route::post('/posts',         [PostController::class, 'store']);
    Route::post('/posts/{post}',  [PostController::class, 'update']);
    Route::delete('/posts/{post}',[PostController::class, 'destroy']);

    // --- Komentar ---
    Route::post('/posts/{post}/comments',   [CommentController::class, 'store']);
    Route::post('/comments/{comment}',      [CommentController::class, 'update']);
    Route::delete('/comments/{comment}',    [CommentController::class, 'destroy']);
});
