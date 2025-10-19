<?php

namespace App\Http\Controllers\Api;

use App\Facades\Authentication;
use App\Http\Controllers\Controller;

use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Requests\Api\Auth\RecoverAccountRequest;
use App\Http\Requests\Api\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\Auth\VerifyEmailRequest;
use App\Http\Requests\Api\Auth\LogoutRequest;
use App\Http\Requests\Api\Auth\ChangePasswordRequest;
use App\Http\Requests\Api\Auth\UpdateProfileRequest;
use App\Http\Requests\Api\Auth\DeleteAccountRequest;

use App\Http\Resources\UserResource;

class AuthController extends Controller
{
    private $tokenName;

    public function __construct()
    {
        $this->tokenName = env("API_TOKEN_NAME", "starter-kit");
    }

    public function postLogin(LoginRequest $request)
    {
        return $this->handle(function () use ($request) {
            $user = Authentication::login($request);
            $token = $user->createToken($this->tokenName);
            return response()->json([
                'user' => new UserResource($user),
                'token' => $token->plainTextToken,
            ], 200);
        });
    }

    public function postRegister(RegisterRequest $request)
    {
        return $this->handle(function () use ($request) {
            $user = Authentication::register($request);
            $token = $user->createToken($this->tokenName);
            return response()->json([
                'user' => new UserResource($user),
                'token' => $token->plainTextToken,
            ], 200);
        });
    }

    public function postRecoverAccount(RecoverAccountRequest $request)
    {
        return $this->handle(function () use ($request) {
            Authentication::recoverAccount($request);
            return response()->json([], 200);
        });
    }

    public function postResetPassword(ResetPasswordRequest $request)
    {
        return $this->handle(function () use ($request) {
            Authentication::resetPassword($request);
            return response()->json([], 200);
        });
    }

    public function postVerifyEmail(VerifyEmailRequest $request)
    {
        return $this->handle(function () use ($request) {
            $user = Authentication::verifyEmail($request);
            return response()->json([], 200);
        });
    }

    public function postLogout(LogoutRequest $request)
    {
        return $this->handle(function () use ($request) {
            Authentication::logout($request);
            return response()->json([], 200);
        });
    }

    public function postChangePassword(ChangePasswordRequest $request)
    {
        return $this->handle(function () use ($request) {
            $user = Authentication::changePassword($request);
            return response()->json([], 200);
        });
    }

    public function postUpdateProfile(UpdateProfileRequest $request)
    {
        return $this->handle(function () use ($request) {
            $user = Authentication::updateProfile($request);
            return response()->json([], 200);
        });
    }

    public function postDeleteAccount(DeleteAccountRequest $request)
    {
        return $this->handle(function () use ($request) {
            Authentication::deleteAccount($request);
            return response()->json([], 200);
        });
    }

    public function getOAuthRedirect()
    {


    }

    public function getOAuthCallback()
    {

    }
}