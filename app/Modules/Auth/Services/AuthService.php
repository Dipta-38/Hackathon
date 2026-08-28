<?php

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function getUser(int $id): ?User
    {
        return User::find($id);
    }

    public function getUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function getCurrentUser(): ?User
    {
        return Auth::user();
    }

    public function isLoggedIn(): bool
    {
        return Auth::check();
    }

    public function getActiveUsers()
    {
        return User::where('status', 'active')->get();
    }
}
