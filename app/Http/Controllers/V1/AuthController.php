<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\RegisterAuthRequest;
use App\Http\Responses\V1\ApiResponse;
use App\Repository\V1\Auth\AuthRepository;
use Exception;
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
            $this->authRepository->register($request);
            return ApiResponse::success("Registro Exitoso", 201);
        } catch (ValidationException $e) {
            $errors = $e->validator->errors()->toArray();
            return ApiResponse::error("Error de validación", 422, $errors);
        } catch (Exception $e) {
            return ApiResponse::error("Ha ocurrido un error: " . $e->getMessage(), 500);
        }
    }
}
