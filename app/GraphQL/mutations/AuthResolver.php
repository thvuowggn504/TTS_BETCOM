<?php

namespace App\GraphQL\Mutations;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthResolver
{
    public function register($_, array $args)
    {
        $user = User::create([
            'name' => $args['name'],
            'email' => $args['email'],
            'password' => $args['password'], // sẽ dùng mutator setPasswordAttribute
        ]);

        $token = Auth::login($user);

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => $user,
        ];
    }

    public function login($_, array $args)
    {
        if (!$token = Auth::attempt([
            'email' => $args['email'],
            'password' => $args['password'],
        ])) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => Auth::user(),
        ];
    }
}
