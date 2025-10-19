<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\AuthenticationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    /** @var AuthenticationService $service */
    $this->service = app(AuthenticationService::class);
});

function makeAuthRequest(string $class, array $payload = [], ?User $user = null)
{
    /** @var Illuminate\Foundation\Http\FormRequest $request */
    $request = $class::create('/', 'POST', $payload);
    $request->setContainer(app());
    $request->setRedirector(app('redirect'));
    $request->setUserResolver(fn () => $user);

    return $request;
}

it('logs in a user with valid credentials', function () {
    $password = 'super-secret';
    $user = User::factory()->create([
        'email' => 'login@example.com',
        'password' => $password,
    ]);

    $request = makeAuthRequest(App\Http\Requests\Auth\LoginRequest::class, [
        'email' => $user->email,
        'password' => $password,
    ]);

    $result = $this->service->login($request);

    expect($result->is($user))->toBeTrue();
    expect(Auth::id())->toBe($user->id);
});

it('throws an exception when credentials are invalid', function () {
    $user = User::factory()->create([
        'email' => 'invalid-login@example.com',
        'password' => 'correct-password',
    ]);

    $request = makeAuthRequest(App\Http\Requests\Auth\LoginRequest::class, [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    expect(fn () => $this->service->login($request))
        ->toThrow(Exception::class, 'Invalid credentials');
    expect(Auth::check())->toBeFalse();
});

it('registers a new user', function () {
    $request = makeAuthRequest(App\Http\Requests\Auth\RegisterRequest::class, [
        'name' => 'Register Name',
        'email' => 'register@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $user = $this->service->register($request);

    expect($user->exists)->toBeTrue();
    expect($user->email_verified_at)->not->toBeNull();
    expect(Hash::check('new-password', $user->password))->toBeTrue();
});

it('stores a password recovery code when a user exists', function () {
    $user = User::factory()->create([
        'email' => 'recover@example.com',
        'password_recovery_code' => null,
    ]);

    $request = makeAuthRequest(App\Http\Requests\Auth\RecoverAccountRequest::class, [
        'email' => $user->email,
    ]);

    $this->service->recoverAccount($request);

    expect($user->fresh()->password_recovery_code)->not->toBeNull();
});

it('silently ignores password recovery for unknown users', function () {
    $request = makeAuthRequest(App\Http\Requests\Auth\RecoverAccountRequest::class, [
        'email' => 'missing@example.com',
    ]);

    expect(fn () => $this->service->recoverAccount($request))->not->toThrow(Exception::class);
    expect(User::count())->toBe(0);
});

it('resets a users password', function () {
    $user = User::factory()->create([
        'email' => 'reset@example.com',
        'password' => 'old-password',
    ]);

    $request = makeAuthRequest(App\Http\Requests\Auth\ResetPasswordRequest::class, [
        'email' => $user->email,
        'recovery_code' => 'recovery-code',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $this->service->resetPassword($request);

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

it('verifies a users email when they exist', function () {
    $user = User::factory()->create([
        'email' => 'verify@example.com',
        'email_verified_at' => null,
        'email_verification_code' => 'code-123',
    ]);

    $request = makeAuthRequest(App\Http\Requests\Auth\VerifyEmailRequest::class, [
        'email' => $user->email,
        'verification_code' => 'code-123',
    ]);

    $this->service->verifyEmail($request);

    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

it('does nothing when verifying a missing user', function () {
    $request = makeAuthRequest(App\Http\Requests\Auth\VerifyEmailRequest::class, [
        'email' => 'void@example.com',
        'verification_code' => 'code-123',
    ]);

    expect(fn () => $this->service->verifyEmail($request))->not->toThrow(Exception::class);
});

it('logs out an authenticated user and clears their token', function () {
    $user = User::factory()->create([
        'email' => 'logout@example.com',
    ]);
    $user->active_character_id = 42;
    $user->save();

    $token = $user->createToken('device');
    $user->withAccessToken($token->accessToken);

    Auth::login($user);

    $request = makeAuthRequest(App\Http\Requests\Auth\LogoutRequest::class, [], $user);

    $this->service->logout($request);

    expect($user->fresh()->active_character_id)->toBeNull();
    expect($user->tokens()->count())->toBe(0);
});

it('changes the password for the authenticated user', function () {
    $user = User::factory()->create([
        'email' => 'change@example.com',
        'password' => 'old-password',
    ]);

    Auth::login($user);

    $request = makeAuthRequest(App\Http\Requests\Auth\ChangePasswordRequest::class, [
        'password' => 'old-password',
        'new_password' => 'brand-new-password',
        'new_password_confirmation' => 'brand-new-password',
    ], $user);

    $this->service->changePassword($request);

    expect(Hash::check('brand-new-password', $user->fresh()->password))->toBeTrue();
});

it('updates the authenticated users profile', function () {
    $user = User::factory()->create([
        'email' => 'old@example.com',
        'name' => 'Old Name',
    ]);

    Auth::login($user);

    $request = makeAuthRequest(App\Http\Requests\Auth\UpdateProfileRequest::class, [
        'name' => 'New Name',
        'email' => 'new@example.com',
    ], $user);

    $updated = $this->service->updateProfile($request);

    expect($updated->name)->toBe('New Name');
    expect($updated->email)->toBe('new@example.com');
    expect($user->fresh()->email)->toBe('new@example.com');
});

it('deletes the authenticated user account', function () {
    $user = User::factory()->create([
        'email' => 'delete@example.com',
    ]);
    $user->active_character_id = 21;
    $user->save();

    $token = $user->createToken('device');
    $user->withAccessToken($token->accessToken);

    Auth::login($user);

    $request = makeAuthRequest(App\Http\Requests\Auth\DeleteAccountRequest::class, [
        'password' => 'password',
        'password_confirmation' => 'password',
    ], $user);

    $this->service->deleteAccount($request);

    expect(User::find($user->id))->toBeNull();
    expect(DB::table('personal_access_tokens')->whereKey($token->accessToken->id)->exists())->toBeFalse();
});
