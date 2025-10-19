<?php

namespace App\Http\Controllers\Api;

use App\Facades\Users;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;

class UserController extends Controller
{
    public function getUser()
    {
        $user = Users::getCurrent();

        return $this->handle(function () {
            "user" => new UserResource($user),
        });
    }

    public function getUsers()
    {
        $users = Users::getAll();

        return $this->handle(function () {
            "users" => UserResource::collect($users),
        });
    }
}