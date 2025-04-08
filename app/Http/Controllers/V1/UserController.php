<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\ChangePasswordRequest;
use App\Http\Requests\V1\User\DeleteAccountRequest;
use App\Http\Responses\V1\ApiResponse;
use App\Repository\V1\Auth\AuthRepository;
use App\Repository\V1\User\UserRepository;
use Exception;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function __construct(private AuthRepository $authRepository, private UserRepository $userRepository) {}

    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            DB::beginTransaction();
            $user = $this->authRepository->userLoggedIn();
            $this->userRepository->changePassword($user, $request);
            DB::commit();
            return ApiResponse::success(
                'Contraseña actualizada correctamente',
                200
            );
        } catch (Exception $e) {
            DB::rollBack();
            return ApiResponse::error("Ha ocurrido un error: " . $e->getMessage(), 500);
        }
    }
    public function deleteMe(DeleteAccountRequest $request)
    {
        try {
            $user = $this->authRepository->userLoggedIn();
            $this->userRepository->deleteMe($user);
            return ApiResponse::success(
                'Cuenta eliminada correctamente',
                204
            );
        } catch (Exception $e) {
            DB::rollBack();
            return ApiResponse::error("Ha ocurrido un error: " . $e->getMessage(), 500);
        }
    }
    public function deleteMeSocial()
    {
        try {
            $user = $this->authRepository->userLoggedIn();
            $this->userRepository->deleteMe($user);
            return ApiResponse::success(
                'Cuenta eliminada correctamente',
                204
            );
        } catch (Exception $e) {
            DB::rollBack();
            return ApiResponse::error("Ha ocurrido un error: " . $e->getMessage(), 500);
        }
    }
}
