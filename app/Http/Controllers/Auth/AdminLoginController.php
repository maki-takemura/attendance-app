<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

class AdminLoginController extends Controller
{
    /**
     * Show the admin login form.
     */
    public function create(): View
    {
        return view('admin.admin-login');
    }

    /**
     * Authenticate an admin user.
     *
     * @throws ValidationException
     */
    public function store(
        LoginRequest $request,
        AuthenticatedSessionController $controller
    ): RedirectResponse {
        $user = User::where('email', $request->input('email'))->first();

        if (! $user || ! $user->admin_status) {
            throw ValidationException::withMessages([
                'email' => 'ログイン情報が登録されていません',
            ]);
        }

        $controller->store($request);

        return redirect('/admin/attendance/list');
    }
}
