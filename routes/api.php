<?php

use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\ProfileController;
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
    });
});
