<?php

use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\ChatController;
use App\Http\Controllers\V1\FriendRequestController;
use App\Http\Controllers\V1\PostController;
use App\Http\Controllers\V1\ProfileController;
use App\Http\Controllers\V1\ProfileMatchingController;
use App\Http\Controllers\V1\StreamingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy bu ilding your API!
|
*/
Route::prefix('v1')->group(function () { 
    Route::post("/auth/register",[AuthController::class,'register']);
    Route::post("/auth/register/verify",[AuthController::class,'verifyUser']);
    Route::post("/auth/login",[AuthController::class,'login']);
    Route::post("/auth/forget-password",[AuthController::class,'forgetPassword']);

    Route::group(['middleware' => ['auth:sanctum']], function () { 
        Route::post("/profile/create",[ProfileController::class,'create']);
        Route::get("/profile/fetch",[ProfileController::class,'fetchProfile']);
        Route::post("/profile/update",[ProfileController::class,'updateProfile']);

        Route::get("/profile-matching/fetch",[ProfileMatchingController::class,'findMatchingProfiles']);
        Route::post("/profile-matching/update-location",[ProfileMatchingController::class,'updateLocation']);

        Route::post("/post/create",[PostController::class,'create']);
        Route::get("/post/fetch",[PostController::class,'fetchPosts']);
        Route::post("/post/like-post",[PostController::class,'likePost']);
        Route::post("/post/dislike-post",[PostController::class,'disLikePost']);

        Route::post('/chat/create', [ChatController::class, 'create']);
        Route::get('/chat/fetch', [ChatController::class, 'fetchMessages']);

        Route::post('/streaming/create', [StreamingController::class, 'create']);
        Route::post('/streaming/end', [StreamingController::class, 'endStream']);
        Route::get('/streaming/fetch', [StreamingController::class, 'fetchStreams']);
        Route::post('/streaming/fetch-by-user', [StreamingController::class, 'fetchStreamsByUserId']);

        Route::post('/friend-request/create', [FriendRequestController::class, 'create']);
        Route::post('/friend-request/update-status', [FriendRequestController::class, 'updateFriendRequestStatus']);
        Route::post('/friend-request/fetch-friends', [FriendRequestController::class, 'fetchFriends']);

    });
});
