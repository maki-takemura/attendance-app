<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

class LoginController extends Controller
{
    /**
     * Authenticate a user.
     */
    public function store(
        LoginRequest $request,
        AuthenticatedSessionController $controller
    ): RedirectResponse {
        return $controller->store($request);
    }
}
