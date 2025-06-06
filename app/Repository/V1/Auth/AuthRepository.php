<?php

namespace App\Repository\V1\Auth;

use App\Events\V1\UserRegisteredEvent;
use App\Http\Resources\V1\Auth\LoginResource;
use App\Http\Responses\V1\ApiResponse;
use Illuminate\Http\Request;
use App\Interfaces\V1\Auth\AuthRepositoryInterface;
use App\Models\V1\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthRepository implements AuthRepositoryInterface
{
    public function login(Request $request)
    {
        $user = User::where('registration_method', 'local')->where("email", "=", $request->email)->with('roles')->first();
        if (isset($user->id)) {
            if (Hash::check($request->password, $user->password)) {
                //creamos el token
                $token = $user->createToken("auth_token")->plainTextToken;
                //si está todo ok
                return ApiResponse::success("Login exitoso", 200, new LoginResource($user, $token));
            } else {

                return ApiResponse::error("Credenciales incorrectas", 401);
            }
        } else {
            return ApiResponse::error("Usuario no registrado", 404);
        }
    }

    public function register(Request $request)
    {
        $user = User::create([
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
        $id = Auth::guard('sanctum')->user()->id;
        $user = User::where('id', $id)->with('roles', 'ranges', 'image', 'followings', 'followers', 'posts', 'comments', 'replayComments', 'reactions', 'complaints','userInformation')->first();
        return $user;
    }
    public function userProfileUserId($id)
    {
        $user = User::where('id', $id)->with('roles', 'ranges', 'image', 'followings', 'followers', 'posts', 'comments', 'replayComments', 'reactions', 'complaints','userInformation')->first();
        if (!$user) {
            throw new ModelNotFoundException("Usuario no encontrado");
        }
        return $user;
    }
    public function userLoggedIn()
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
            return ApiResponse::error("El correo ya fue verificado", 301);
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
            event(new UserRegisteredEvent($request->user()));
            return ApiResponse::success("Correo electrónico verificado exitosamente.", 200);
        }
    }

    public function forgot_password(Request $request)
    {
        $user = User::where('email', $request->email)
            ->where('registration_method', 'local')
            ->first();
        if (!$user) {
            throw new ModelNotFoundException("Usuario no registrado");
        }
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? ApiResponse::success("Enlace de restablecimiento enviado.", 200)
            : ApiResponse::error("Error al enviar el enlace de restablecimiento.", 400, ["error" => $status]);
    }

    public function reset_password(Request $request)
    {
        DB::beginTransaction();
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->saveOldPassword();
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );
        DB::commit();
        return $status === Password::PASSWORD_RESET
            ? ApiResponse::success("Contraseña cambiada.", 200)
            : ApiResponse::error("Error al cambiar la contraseña.", 400, ["error" => $status]);
    }
}
