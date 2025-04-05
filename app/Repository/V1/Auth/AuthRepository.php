<?php

namespace App\Repository\V1\Auth;

use App\Http\Resources\V1\Auth\LoginResource;
use App\Http\Responses\V1\ApiResponse;
use Illuminate\Http\Request;
use App\Interfaces\V1\Auth\AuthRepositoryInterface;
use App\Models\V1\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Auth;
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
        $token = $user->createToken("auth_token")->plainTextToken;
        $user->assignRole('client');
        event(new Registered($user));
        return $token;
    }

    public function userProfile()
    {
       return Auth::guard('sanctum')->user();
    }

    public function logout()
    {
        Auth::guard('sanctum')->user()->tokens()->delete();
    }

    public function sendVerificationEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return ApiResponse::error("La cuenta ya fue verificado", 409);
        }
        $request->user()->sendEmailVerificationNotification();
        return ApiResponse::success("Correo de verificación enviado.", 200);
    }

    public function verifyEmail(EmailVerificationRequest $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return ApiResponse::error("El correo ya fue verificado", 200);
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }
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
