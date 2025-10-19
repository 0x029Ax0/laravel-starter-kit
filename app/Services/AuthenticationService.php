<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\DeleteAccountRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\LogoutRequest;
use App\Http\Requests\Auth\RecoverAccountRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class AuthenticationService
{
    public function login(LoginRequest $request): User
    {
        $user = User::where('email', $request->email)->first();

        if (! $user or ! Hash::check($request->password, $user->password)) {
            throw new Exception('Invalid credentials');
        }

        auth()->login($user);

        return auth()->user();
    }

    public function register(RegisterRequest $request): User
    {
        return User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'email_verified_at' => now(),
        ]);
    }

    public function recoverAccount(RecoverAccountRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->password_recovery_code = Str::uuid();
            $user->save();
        }
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        $user->password = bcrypt($request->password);
        $user->save();
    }

    public function verifyEmail(VerifyEmailRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return;
        }

        if ($user->email_verification_code !== $user->email_verification_code) {
            return;
        }

        $user->email_verified_at = now();
        $user->save();
    }

    public function logout(LogoutRequest $request)
    {
        $this->performLogout($request);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = auth()->user();
        $user->password = bcrypt($request->new_password);
        $user->save();
    }

    public function updateProfile(UpdateProfileRequest $request): User
    {
        $user = auth()->user();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        return $user;
    }

    public function deleteAccount(DeleteAccountRequest $request)
    {
        $user = auth()->user();

        $this->performLogout($request);

        $user->delete();
    }

    private function performLogout(LogoutRequest|DeleteAccountRequest $request)
    {
        $user = auth()->user();
        $user->active_character_id = null;
        $user->save();

        $token = $request->user()?->currentAccessToken();

        // Only delete if it's a real token (not a TransientToken)
        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }
    }
}
