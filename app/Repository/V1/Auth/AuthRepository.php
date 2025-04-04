<?php

namespace App\Repository\V1\Auth;

use Illuminate\Http\Request;
use App\Interfaces\V1\Auth\AuthRepositoryInterface;
use App\Models\V1\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthRepository implements AuthRepositoryInterface
{
    public function login(Request $request)
    {
        // Implement login logic here
    }

    public function register(Request $request)
    {
        $user=User::create([
            'name' => $request->name,
            'lastname' => $request->lastname,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        $user->assignRole('client');
    }

    public function userProfile()
    {
        // Implement user profile logic here
    }

    public function logout()
    {
        // Implement logout logic here
    }

    public function sendVerificationEmail(Request $request)
    {
        // Implement send verification email logic here
    }

    public function verifyEmail(\Illuminate\Foundation\Auth\EmailVerificationRequest $request)
    {
        // Implement verify email logic here
    }

    public function forgot_password(Request $request)
    {
        // Implement forgot password logic here
    }

    public function reset_password(Request $request)
    {
        // Implement reset password logic here
    }
}
