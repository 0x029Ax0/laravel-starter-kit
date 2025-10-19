<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Facades\Users;
use App\Http\Resources\UserResource;

final class UserController extends Controller
{
    public function getUser()
    {
        return $this->handle(function () {
            $user = Users::getCurrent();

            return response()->json([
                'user' => new UserResource($user),
            ]);
        });
    }

    public function getUsers()
    {
        return $this->handle(function () {
            $users = Users::getAll();

            return response()->json([
                'users' => UserResource::collect($users),
            ]);
        });
    }
}
