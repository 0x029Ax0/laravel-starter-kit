<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('returns the currently authenticated user', function () {
    $service = app(UserService::class);

    $user = User::factory()->create();
    Auth::login($user);

    expect($service->getCurrent()?->is($user))->toBeTrue();
});

it('returns a collection of all users', function () {
    $service = app(UserService::class);

    $users = User::factory()->count(3)->create();

    $result = $service->getUsers();

    expect($result)->toHaveCount(3);
    expect($result->pluck('id')->sort()->values())->toEqual($users->pluck('id')->sort()->values());
});
