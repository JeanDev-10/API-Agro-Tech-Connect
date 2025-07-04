<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\V1\Auth\LoginAuthRequest;
use App\Http\Requests\V1\Auth\RegisterAuthRequest;
use App\Http\Requests\V1\Auth\ResetPasswordRequest;
use App\Http\Resources\V1\User\UserResource;
use App\Http\Responses\V1\ApiResponse;
use App\Repository\V1\Auth\AuthRepository;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        protected
        AuthRepository $authRepository,
    ) {}
    public function register(RegisterAuthRequest $request)
    {
        try {
            $token = $this->authRepository->register($request);
            return ApiResponse::success("Registro Exitoso", 201, ['token' => $token]);
        } catch (ValidationException $e) {
            $errors = $e->validator->errors()->toArray();
            return ApiResponse::error("Error de validación", 422, $errors);
        } catch (Exception $e) {
            return ApiResponse::error("Ha ocurrido un error: " . $e->getMessage(), 500);
        }
    }
    public function login(LoginAuthRequest $request)
    {
        try {
            return $this->authRepository->login($request);
        } catch (ModelNotFoundException) {
            return ApiResponse::error("Usuario no registrado", 404);
        } catch (Exception $e) {
            return ApiResponse::error("Ha ocurrido un error: " . $e->getMessage(), 500);
        }
    }
    public function logout()
    {
        try {
            $this->authRepository->logout();
            return ApiResponse::success("Logout exitoso", 200);
        } catch (Exception $e) {
            return ApiResponse::error("Ha ocurrido un error: " . $e->getMessage(), 500);
        }
    }
    public function userProfile()
    {
        try {
            $user = $this->authRepository->userProfile();
            return ApiResponse::success("Perfil de usuario", 200, new UserResource($user));

        } catch (Exception $e) {
            return ApiResponse::error("Ha ocurrido un error: " . $e->getMessage(), 500);
        }
    }
    public function userProfileUserId(Request $request)
    {
        try {
            $id=Crypt::decrypt($request->id);
            $user = $this->authRepository->userProfileUserId($id);
            return ApiResponse::success("Perfil de usuario", 200, new UserResource($user));
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error("Usuario no encontrado" , 404);
        } catch (Exception $e) {
            return ApiResponse::error("Ha ocurrido un error: " . $e->getMessage(), 500);
        }
    }

    public function verifyEmail(EmailVerificationRequest $request)
    {
        try {
            return $this->authRepository->verifyEmail($request);
        } catch (Exception $e) {
            return ApiResponse::error("Ha ocurrido un error: " . $e->getMessage(), 500);
        }
    }
    public function sendVerificationEmail(Request $request)
    {
        try {
            return $this->authRepository->sendVerificationEmail($request);
        } catch (Exception $e) {
            return ApiResponse::error("Ha ocurrido un error: " . $e->getMessage(), 500);
        }
    }
    public function forgot_password(ForgotPasswordRequest $request)
    {
        try {
            return $this->authRepository->forgot_password($request);
        } catch (ModelNotFoundException) {
            return ApiResponse::error("Usuario no registrado", 404);
        } catch (Exception $e) {
            return ApiResponse::error("Ha ocurrido un error: " . $e->getMessage(), 500);
        }
    }
    public function reset_password(ResetPasswordRequest $request)
    {
        try {
            return $this->authRepository->reset_password($request);
        } catch (ModelNotFoundException) {
            return ApiResponse::error("Usuario no registrado", 404);
        } catch (Exception $e) {
            DB::rollBack();
            return ApiResponse::error("Ha ocurrido un error: " . $e->getMessage(), 500);
        }
    }
}
