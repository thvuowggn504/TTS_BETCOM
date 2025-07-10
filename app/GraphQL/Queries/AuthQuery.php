<?php

namespace App\GraphQL\Queries;

use Illuminate\Support\Facades\Auth;

class AuthQuery
{
    public function me(): \App\Models\User
    {
        return Auth::user();
    }
}
