<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthenticateLoginAttempt
{
    public function __invoke(Request $request)
    {
        $identity = trim((string) $request->email);

        if (filter_var($identity, FILTER_VALIDATE_EMAIL)) {
            $user = User::query()->where('email', mb_strtolower($identity))->first();
        } else {
            $digitsOnly = preg_replace('/\D+/', '', $identity) ?: $identity;
            $user = User::query()->where('phone', $digitsOnly)->first();
        }

        if ($user && Hash::check($request->password, $user->password)) {
            return $user;
        }
    }
}
