<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1'], function () {

    // Authentication routes
    Route::group(['prefix' => 'auth'], function () {
        Route::post('login', [AuthController::class, 'postLogin']);
        Route::post('register', [AuthController::class, 'postRegister']);
        Route::post('recover-account', [AuthController::class, 'postRecoverAccount']);
        Route::post('reset-password', [AuthController::class, 'postResetPassword']);
        Route::post('verify-email', [AuthController::class, 'postVerifyEmail']);
        Route::group(['middleware' => 'auth:sanctum'], function () {
            Route::post('logout', [AuthController::class, 'postLogout']);
            Route::post('change-password', [AuthController::class, 'postChangePassword']);
            Route::post('update-profile', [AuthController::class, 'postUpdateProfile']);
            Route::post('delete-account', [AuthController::class, 'postDeleteAccount']);
            Route::post('refresh', [AuthController::class, 'postRefresh']);
        });
    });

    // OAuth
    Route::group(['prefix' => 'oauth', 'middleware' => 'web'], function () {
        Route::get('redirect/{provider}', [AuthController::class, 'getOAuthRedirect']);
        Route::get('callback/{provider}', [AuthController::class, 'getOAuthCallback']);
    });

    // User routes
    Route::group(['prefix' => 'users'], function () {
        Route::get('user', [UserController::class, 'getUser']);
        Route::get('/', [UserController::class, 'getUsers']);
    });

});
