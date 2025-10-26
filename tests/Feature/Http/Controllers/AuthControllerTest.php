<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Laravel\Socialite\Facades\Socialite;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

it('logs in a user and returns a token', function () {
    $user = User::factory()->create([
        'email' => 'login@example.com',
        'password' => 'password',
    ]);

    $response = postJson('/api/v1/auth/login', [
        'email' => 'login@example.com',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);

    expect($response->json('token'))->toBeString()->not->toBe('');
});

it('returns an error response when login fails', function () {
    $response = postJson('/api/v1/auth/login', [
        'email' => 'login@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(500)
        ->assertJson([
            'message' => 'Internal Server Error',
            'error' => 'Invalid credentials',
        ]);
});

it('registers a user and returns a token', function () {
    $response = postJson('/api/v1/auth/register', [
        'name' => 'Register User',
        'email' => 'register@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('user.email', 'register@example.com')
        ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);

    $user = User::whereEmail('register@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->email_verified_at)->not->toBeNull();
});

it('responds with success after account recovery request', function () {
    $user = User::factory()->create([
        'email' => 'recover@example.com',
        'password_recovery_code' => null,
    ]);

    $response = postJson('/api/v1/auth/recover-account', [
        'email' => 'recover@example.com',
    ]);

    $response->assertOk()->assertExactJson([
        'status' => 'success',
    ]);

    expect($user->fresh()->password_recovery_code)->not->toBeNull();
});

it('responds with success after password reset request', function () {
    User::factory()->create([
        'email' => 'reset@example.com',
        'password' => 'old-password',
    ]);

    $response = postJson('/api/v1/auth/reset-password', [
        'email' => 'reset@example.com',
        'recovery_code' => 'recovery-code',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertOk()->assertExactJson([
        'status' => 'success',
    ]);

    $user = User::whereEmail('reset@example.com')->first();
    expect(Hash::check('new-password', $user->password))->toBeTrue();
});

it('responds with success after email verification request', function () {
    $user = User::factory()->create([
        'email' => 'verify@example.com',
        'email_verified_at' => null,
        'email_verification_code' => 'code-123',
    ]);

    $response = postJson('/api/v1/auth/verify-email', [
        'email' => 'verify@example.com',
        'verification_code' => 'code-123',
    ]);

    $response->assertOk()->assertExactJson([
        'status' => 'success',
    ]);

    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

it('logs out an authenticated user', function () {
    $user = User::factory()->create([
        'active_character_id' => 42,
    ]);

    Sanctum::actingAs($user);

    $response = postJson('/api/v1/auth/logout');

    $response->assertOk()->assertExactJson([
        'status' => 'success',
    ]);

    expect($user->fresh()->active_character_id)->toBeNull();
});

it('refreshes the authenticated user session', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = postJson('/api/v1/auth/refresh');

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
        ])
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonStructure(['user' => ['id', 'name', 'email']]);
});

it('changes the password for an authenticated user', function () {
    $user = User::factory()->create([
        'password' => 'old-password',
    ]);

    Sanctum::actingAs($user);

    $response = postJson('/api/v1/auth/change-password', [
        'password' => 'old-password',
        'new_password' => 'new-password',
        'new_password_confirmation' => 'new-password',
    ]);

    $response->assertOk()->assertExactJson([
        'status' => 'success',
    ]);

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

it('updates the profile for an authenticated user', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old-email@example.com',
    ]);

    Sanctum::actingAs($user);

    $response = postJson('/api/v1/auth/update-profile', [
        'name' => 'New Name',
        'email' => 'new-email@example.com',
    ]);

    $response->assertOk()->assertExactJson([
        'status' => 'success',
    ]);

    $user->refresh();
    expect($user->name)->toBe('New Name');
    expect($user->email)->toBe('new-email@example.com');
});

it('deletes the account for an authenticated user', function () {
    $user = User::factory()->create([
        'email' => 'delete@example.com',
    ]);

    Sanctum::actingAs($user);

    $response = postJson('/api/v1/auth/delete-account', [
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertOk()->assertExactJson([
        'status' => 'success',
    ]);

    expect(User::whereEmail('delete@example.com')->exists())->toBeFalse();
});

it('provides an oauth redirect url for a provider', function () {
    $redirectUrl = 'https://oauth.example.com/redirect';
    $headers = Mockery::mock();
    $headers->shouldReceive('get')->with('location')->andReturn($redirectUrl);

    $redirectResponse = new class($headers)
    {
        public function __construct(public $headers) {}
    };

    $driver = Mockery::mock();
    $driver->shouldReceive('redirect')->andReturn($redirectResponse);

    Socialite::shouldReceive('driver')
        ->once()
        ->with('google')
        ->andReturn($driver);

    $response = getJson('/api/v1/auth/oauth/redirect/google');

    $response->assertOk()->assertJson(['redirect_url' => $redirectUrl]);
});

it('returns an error when oauth provider is invalid', function () {
    $response = getJson('/api/v1/auth/oauth/callback/invalid');

    $response->assertStatus(500)
        ->assertJson([
            'message' => 'Internal Server Error',
            'error' => 'Invalid provider received.',
        ]);
});

it('handles oauth callback for a known provider by creating a token', function () {
    putenv('FRONTEND_URL=https://frontend.test');
    $_ENV['FRONTEND_URL'] = 'https://frontend.test';
    $_SERVER['FRONTEND_URL'] = 'https://frontend.test';

    $oauthUser = (object) [
        'email' => 'oauth@example.com',
        'id' => 'oauth-id',
        'avatar' => 'https://avatars.example.com/avatar.png',
        'name' => 'OAuth User',
    ];

    $driver = Mockery::mock();
    $driver->shouldReceive('stateless')->andReturnSelf();
    $driver->shouldReceive('user')->andReturn($oauthUser);

    Socialite::shouldReceive('driver')
        ->once()
        ->with('google')
        ->andReturn($driver);

    $response = get('/api/v1/auth/oauth/callback/google');

    $response->assertRedirect();
    $location = $response->headers->get('location');
    expect($location)->toStartWith('https://frontend.test/oauth/callback/');

    $user = User::whereEmail('oauth@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->oauth_provider)->toBe('google');
    expect($user->oauth_provider_id)->toBe('oauth-id');
    expect($user->avatar_url)->toBe($oauthUser->avatar);
});
