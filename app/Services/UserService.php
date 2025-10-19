<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class UserService
{
    public function getCurrent(): ?User
    {
        return auth()->user();
    }

    public function getUsers(): Collection
    {
        return User::all();
    }
}
