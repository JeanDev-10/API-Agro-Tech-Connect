<?php

namespace App\Repository\V1\Auth;

use App\Http\Resources\V1\Auth\LoginResource;
use App\Http\Responses\V1\ApiResponse;
use Illuminate\Http\Request;
use App\Interfaces\V1\Auth\AuthRepositoryInterface;
use App\Models\V1\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthRepository implements AuthRepositoryInterface
{
    public function login(Request $request)
    {
        // Imple$user = User::where("email", "=", $request->email)->first();
        $user = User::where('registration_method','local')->where("email", "=", $request->email)->with('roles')->first();
        if (isset($user->id)) {
            if (Hash::check($request->password, $user->password)) {
                //creamos el token
                $token = $user->createToken("auth_token")->plainTextToken;
                //si está todo ok
                return ApiResponse::success("Login exitoso", 200, new LoginResource($user,$token));
            } else {
                
                return ApiResponse::error("Credenciales incorrectas", 401);
            }
        } else {
            return ApiResponse::error("Usuario no registrado", 404);
        }
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
        Auth::guard('sanctum')->user()->tokens()->delete();
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
