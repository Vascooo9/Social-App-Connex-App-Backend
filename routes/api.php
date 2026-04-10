<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CommentController;
use App\Http\Controllers\API\FollowController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\PostController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Profil sendiri
    Route::post('/profile',         [UserController::class, 'updateProfile']);
    Route::delete('/profile/photo', [UserController::class, 'removeProfilePhoto']);

    // Profil user lain & follow
    Route::get('/users/{username}',             [UserProfileController::class, 'show']);
    Route::get('/users/{username}/followers',   [FollowController::class, 'followers']);
    Route::get('/users/{username}/following',   [FollowController::class, 'following']);
    Route::post('/users/{username}/follow',     [FollowController::class, 'follow']);
    Route::delete('/users/{username}/unfollow', [FollowController::class, 'unfollow']);

    // ⚠️ Route statis HARUS sebelum route {post}
    Route::get('/posts/following',          [PostController::class, 'followingFeed']);
    Route::get('/posts/saved',              [PostController::class, 'savedPosts']);

    // Postingan
    Route::get('/posts',                    [PostController::class, 'index']);
    Route::post('/posts',                   [PostController::class, 'store']);
    Route::post('/posts/{post}',            [PostController::class, 'update']);
    Route::delete('/posts/{post}',          [PostController::class, 'destroy']);
    Route::post('/posts/{post}/like',       [PostController::class, 'toggleLike']);
    Route::post('/posts/{post}/repost',     [PostController::class, 'toggleRepost']);
    Route::post('/posts/{post}/save',       [PostController::class, 'toggleSave']);

    // Komentar
    Route::post('/posts/{post}/comments',   [CommentController::class, 'store']);
    Route::post('/comments/{comment}',      [CommentController::class, 'update']);
    Route::delete('/comments/{comment}',    [CommentController::class, 'destroy']);

    // ⚠️ Route statis HARUS sebelum route {notification}
    Route::get('/notifications',                      [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count',         [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/mark-all-read',       [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
});