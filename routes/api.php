<?php

use App\Http\Controllers\V1\CommentController;
use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\V1\Admin\ChatController as AdminChatController;
use App\Http\Controllers\V1\Admin\ReportController as AdminReportControler;
use App\Http\Controllers\V1\Admin\VideoSdkController as AdminVideoSdkController;
use App\Http\Controllers\V1\ChatController;
use App\Http\Controllers\V1\FriendRequestController;
use App\Http\Controllers\V1\GroupController;
use App\Http\Controllers\V1\NotificationController;
use App\Http\Controllers\V1\PostController;
use App\Http\Controllers\V1\ProfileController;
use App\Http\Controllers\V1\ProfileMatchingController;
use App\Http\Controllers\V1\ReportController;
use App\Http\Controllers\V1\StreamingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () { 
    Route::post("/auth/register",[AuthController::class,'register']);
    Route::post("/auth/register/verify",[AuthController::class,'verifyUser']);
    Route::post("/auth/login",[AuthController::class,'login']);
    Route::post("/auth/forget-password",[AuthController::class,'forgetPassword']);
    Route::get('/streaming/test', [StreamingController::class, 'test']);

    Route::prefix('admin')->group(function () {  
        Route::post("/auth/login",[AdminAuthController::class,'login']);
    });

    Route::group(['middleware' => ['auth:sanctum']], function () { 
        Route::post("/profile/create",[ProfileController::class,'create']);
        Route::get("/profile/fetch",[ProfileController::class,'fetchProfile']);
        Route::get("/profile/fetch-profile-by-id",[ProfileController::class,'fetchProfileById']);
        Route::post("/profile/update",[ProfileController::class,'updateProfile']);

        Route::get("/profile-matching/fetch",[ProfileMatchingController::class,'findMatchingProfiles']);
        Route::post("/profile-matching/update-location",[ProfileMatchingController::class,'updateLocation']);

        Route::post("/post/create",[PostController::class,'create']);
        Route::get("/post/fetch",[PostController::class,'fetchPosts']);
        Route::post("/post/like-post",[PostController::class,'likePost']);
        Route::post("/post/dislike-post",[PostController::class,'disLikePost']);

        Route::post('/chat/create', [ChatController::class, 'create']);
        Route::get('/chat/fetch', [ChatController::class, 'fetchMessages']);
        Route::get('/chat/fetch-chats', [ChatController::class, 'fetchChatList']);

        Route::post('/streaming/create', [StreamingController::class, 'create']);
        Route::post('/streaming/end', [StreamingController::class, 'endStream']);
        Route::get('/streaming/fetch', [StreamingController::class, 'fetchStreams']);
        Route::get('/streaming/fetch-adjacent', [StreamingController::class, 'fetchAdjacentStreams']);
        Route::post('/streaming/fetch-by-user', [StreamingController::class, 'fetchStreamsByUserId']);
        
        Route::post('/friend-request/create', [FriendRequestController::class, 'create']);
        Route::get('/friend-request/fetch-status', [FriendRequestController::class, 'fetchFriendRequestStatus']);
        Route::post('/friend-request/update-status', [FriendRequestController::class, 'updateFriendRequestStatus']);
        Route::get('/friend-request/fetch-friends', [FriendRequestController::class, 'fetchFriends']);
        Route::get('/friend-request/fetch', [FriendRequestController::class, 'fetchFriendRequest']);

        Route::post('/comment/create', [CommentController::class, 'commentOnPost']);
        Route::post('/comment/reply', [CommentController::class, 'replyToComment']);
        Route::get('/comment/fetch', [CommentController::class, 'fetchCommentsWithReplies']);

        Route::post('/group/create', [GroupController::class, 'create']);
        Route::get('/group/fetch', [GroupController::class, 'fetchGroupInfo']);
        Route::post('/group/leave', [GroupController::class, 'leaveGroup']);
        Route::post('/group/add-users-to-group', [GroupController::class, 'addAdditionalUserIdToGroupId']);
        Route::post('/group/remove-users-from-group', [GroupController::class, 'removeUsersFromGroup']);
        Route::post('/group/edit-name', [GroupController::class, 'editGroupName']);

        Route::post('/report/create',[ReportController::class, 'create']);
        
        Route::get('/notification/fetch', [NotificationController::class, 'fetchNotications']);

        Route::prefix('admin')->group(function () {  
            Route::get("/users/fetch",[AdminUserController::class,'fetchUsers']);
            Route::get("/users/fetch-profile-by-id",[AdminUserController::class,'fetchProfileById']);
            Route::get("/users/search",[AdminUserController::class,'searchUsers']);
            Route::post("/users/update-status",[AdminUserController::class,'updateUserStatus']);

            Route::get("/chats/fetch",[AdminChatController::class,'fetchMessages']);
            Route::get("/chats/fetch-groups",[AdminChatController::class,'fetchGroups']);

            Route::get("/reports/fetch",[AdminReportControler::class,'fetchReports']);
            Route::post('/reports/update-status', [AdminReportControler::class,'updateStatus']);

            Route::get("/streams/fetch",[AdminVideoSdkController::class,'fetchStreams']);

        });
    });
});
