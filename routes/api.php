<?php

use App\Http\Controllers\V1\AuthController;
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
Route::prefix('v1')->group(function () { 
    Route::post("/auth/register",[AuthController::class,'register']);
    Route::post("/auth/register/verify",[AuthController::class,'verifyUser']);
    Route::post("/auth/login",[AuthController::class,'login']);
    Route::post("/auth/forget-password",[AuthController::class,'forgetPassword']);
    // Route::post("/auth/forget-password/verify",[AuthController::class,'forgetPasswordVerifyUser']);
    // Route::post("/auth/forget-password/change-password",[AuthController::class,'forgetPasswordChangePassword']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
