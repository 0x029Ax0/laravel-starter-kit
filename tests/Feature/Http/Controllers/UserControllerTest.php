<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

it('returns the current user', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = getJson('/api/v1/users/user');

    $response->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.email', $user->email);
});

it('returns all users', function () {
    $users = User::factory()->count(3)->create();

    $response = getJson('/api/v1/users');

    $response->assertOk();

    $ids = collect($response->json('users'))->pluck('id')->sort()->values();
    expect($ids)->toEqual($users->pluck('id')->sort()->values());
});
