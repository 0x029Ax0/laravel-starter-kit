<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

Route::group(['prefix' => 'v1'], function () {

    // Authentication routes
    Route::group(['prefix' => 'auth'], function () {
        Route::post('login', [AuthController::class, 'postLogin']);
        Route::post('register', [AuthController::class, 'postRegister']);
        Route::post('recover-account', [AuthController::class, 'postRecoverAccount']);
        Route::post('reset-password', [AuthController::class, 'postResetPassword']);
        Route::post('verify-email-address', [AuthController::class, 'postVerifyEmailAddress']);
        Route::group(['middleware' => 'auth:sanctum'], function () {
            Route::get('user', [AuthController::class, 'getUser']);
            Route::post('logout', [AuthController::class, 'postLogout']);
            Route::post('change-password', [AuthController::class, 'postChangePassword']);
            Route::post('update-profile', [AuthController::class, 'postUpdateProfile']);
            Route::post('delete-account', [AuthController::Class, 'postDeleteAccount']);
        });
        Route::group(['prefix' => 'oauth', 'middleware' => 'web'], function () {
            Route::get('redirect/{provider}', [AuthController::class, 'getOAuthRedirect']);
            Route::get('callback/{provider}', [AuthController::class, 'getOAuthCallback']);
        });
    });

    // User routes
    Route::group(['prefix' => 'users'], function () {
        Route::get('/', [UserController::class, 'getUsers']);
    });

});
