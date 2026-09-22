<?php

namespace App\Actions\Fortify;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    /** @param array<string, string> $input */
    public function create(array $input): User
    {
        $validated = Validator::make(
            $input,
            RegisterRequest::registrationRules(),
            RegisterRequest::registrationMessages()
        )->validate();

        return User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'admin_status' => false,
        ]);
    }
}
