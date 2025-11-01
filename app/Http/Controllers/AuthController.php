<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Facades\Authentication;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\DeleteAccountRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\LogoutRequest;
use App\Http\Requests\Auth\RecoverAccountRequest;
use App\Http\Requests\Auth\RefreshRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

final class AuthController extends Controller
{
    private $tokenName;

    private $supportedProviders;

    public function __construct()
    {
        $this->tokenName = env('API_TOKEN_NAME', 'starter-kit');
        $this->supportedProviders = ['github', 'google'];
    }

    public function postLogin(LoginRequest $request)
    {
        return $this->handle(function () use ($request) {
            try {
                $user = Authentication::login($request);
                $token = $user->createToken($this->tokenName);

                return response()->json([
                    'status' => 'success',
                    'user' => new UserResource($user),
                    'token' => $token->plainTextToken,
                ], 200);
            } catch (Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }
        });
    }

    public function postRegister(RegisterRequest $request)
    {
        return $this->handle(function () use ($request) {
            $user = Authentication::register($request);
            $token = $user->createToken($this->tokenName);

            return response()->json([
                'status' => 'success',
                'user' => new UserResource($user),
                'token' => $token->plainTextToken,
            ], 200);
        });
    }

    public function postRecoverAccount(RecoverAccountRequest $request)
    {
        return $this->handle(function () use ($request) {
            Authentication::recoverAccount($request);

            return response()->json([
                'status' => 'success',
            ], 200);
        });
    }

    public function postResetPassword(ResetPasswordRequest $request)
    {
        return $this->handle(function () use ($request) {
            Authentication::resetPassword($request);

            return response()->json([
                'status' => 'success',
            ], 200);
        });
    }

    public function postVerifyEmail(VerifyEmailRequest $request)
    {
        return $this->handle(function () use ($request) {
            $user = Authentication::verifyEmail($request);

            return response()->json([
                'status' => 'success',
            ], 200);
        });
    }

    public function postLogout(LogoutRequest $request)
    {
        return $this->handle(function () use ($request) {
            Authentication::logout($request);

            return response()->json([
                'status' => 'success',
            ], 200);
        });
    }

    public function postChangePassword(ChangePasswordRequest $request)
    {
        return $this->handle(function () use ($request) {
            $user = Authentication::changePassword($request);

            return response()->json([
                'status' => 'success',
            ], 200);
        });
    }

    public function postUpdateProfile(UpdateProfileRequest $request)
    {
        return $this->handle(function () use ($request) {
            $user = Authentication::updateProfile($request);

            return response()->json([
                'status' => 'success',
                'user' => new UserResource($user),
            ], 200);
        });
    }

    public function postDeleteAccount(DeleteAccountRequest $request)
    {
        return $this->handle(function () use ($request) {
            Authentication::deleteAccount($request);

            return response()->json([
                'status' => 'success',
            ], 200);
        });
    }

    public function getOAuthRedirect(string $provider)
    {
        return $this->handle(function () use ($provider) {
            switch ($provider) {
                default:
                case 'google':
                    $url = Socialite::driver('google')->redirect()->headers->get('location');
                    break;
                case 'github':
                    $url = Socialite::driver('github')->redirect()->headers->get('location');
                    break;
            }

            return response()->json(['redirect_url' => $url]);
        });
    }

    public function getOAuthCallback(string $provider)
    {
        return $this->handle(function () use ($provider) {
            // Ensure provider is supported
            if (! in_array($provider, $this->supportedProviders)) {
                throw new Exception('Invalid provider received.');
            }

            // Retrieve user's account from the provider service
            $oauthUser = Socialite::driver($provider)->stateless()->user();

            // Attempt to find the user based on their provider service account's email address
            $user = User::where('email', $oauthUser->email)->first();

            // Check if the user exists but with a different provider
            if ($user && $user->oauth_provider !== null && $user->oauth_provider !== $provider) {
                throw new Exception('You have already signed up with a different platform.');
            }

            // Check if the user exists with the given provider
            if ($user && $user->oauth_provider === $provider) {
                $user->oauth_provider_id = $oauthUser->id;
                $user->avatar_url = $oauthUser->avatar;
                $user->save();
            }

            // Check if the user exists but is not associated with a oauth provider yet
            if ($user && $user->oauth_provider === null) {
                $user->oauth_provider = $provider;
                $user->oauth_provider_id = $oauthUser->id;
                $user->avatar_url = $oauthUser->avatar;
                $user->save();
            }

            // If user does not exist, create it
            if (! $user) {
                $user = User::create([
                    'name' => $oauthUser->name,
                    'email' => $oauthUser->email,
                    'email_verified_at' => now(),
                    'password' => Hash::make(Str::random(40)),
                    'oauth_provider' => $provider,
                    'oauth_provider_id' => $oauthUser->id,
                    'avatar_url' => $oauthUser->avatar,
                ]);
            }

            // Login the user
            auth()->login($user);

            // Create a token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Redirect to the frontend with the token
            return redirect(env('FRONTEND_URL').'/oauth/callback/'.$token);
        });
    }

    public function postRefresh(RefreshRequest $request)
    {
        return $this->handle(function () {
            $user = auth()->user();

            return response()->json([
                'status' => 'success',
                'user' => new UserResource($user),
            ]);
        });
    }
}
