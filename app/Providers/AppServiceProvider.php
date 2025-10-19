<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\AuthenticationService;
use App\Services\UserService;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('authentication', function () {
            return new AuthenticationService;
        });

        $this->app->singleton('users', function () {
            return new UserService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
