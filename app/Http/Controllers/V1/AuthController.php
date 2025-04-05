<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\LoginAuthRequest;
use App\Http\Requests\V1\Auth\RegisterAuthRequest;
use App\Http\Responses\V1\ApiResponse;
use App\Repository\V1\Auth\AuthRepository;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
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
            $token=$this->authRepository->register($request);
            return ApiResponse::success("Registro Exitoso", 201,['token' => $token]);
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
    public function userProfile(){
        try {
            $user=$this->authRepository->userProfile();
            return ApiResponse::success("Perfil de usuario", 200, $user);
        } catch (Exception $e) {
            return ApiResponse::error("Ha ocurrido un error: " . $e->getMessage(), 500);
        }
    }
    public function verifyEmail(EmailVerificationRequest $request){
        try {
            $this->authRepository->verifyEmail($request);
            return ApiResponse::success("Correo electrónico verificado exitosamente.", 200);
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
}
