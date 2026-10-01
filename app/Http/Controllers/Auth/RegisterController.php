<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;

class RegisterController extends Controller
{
    /**
     * Register a new user.
     */
    public function store(
        RegisterRequest $request,
        RegisteredUserController $controller,
        CreatesNewUsers $creator
    ): RegisterResponse {
        return $controller->store($request, $creator);
    }
}
