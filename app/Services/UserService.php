<?php

namespace App\Services;

use App\Models\User;

use Illuminate\Database\Eloquent\Collection;

class UserService
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