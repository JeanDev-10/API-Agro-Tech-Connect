<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\UserInformationRequest;
use App\Http\Resources\V1\User\UserInformationResource;
use App\Http\Responses\V1\ApiResponse;
use App\Models\V1\UserInformation;
use App\Repository\V1\Auth\AuthRepository as AuthAuthRepository;
use App\Repository\V1\User\UserInformationRepository;
use App\Repository\V1\User\AuthRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserInformationController extends Controller
{
    /**
     * Create or update user information.
     *
     * @param UserInformationRequest $request
     * @return JsonResponse
     */
    public function __construct(private AuthAuthRepository $authRepository, private UserInformationRepository $userInformationRepository) {}
    public function storeOrUpdate(UserInformationRequest $request): JsonResponse
    {
        try {
            $user = $this->authRepository->userLoggedIn();
            $userInformation = $this->userInformationRepository->storeOrUpdate($user, $request);
            return ApiResponse::success(
                'Información del usuario actualizada correctamente',
                200,
                new UserInformationResource($userInformation)
            );
        } catch (Exception $e) {
            return ApiResponse::error("Ha ocurrido un error: " . $e->getMessage(), 500);
        }
    }

    /**
     * Get user information.
     *
     * @return JsonResponse
     */
    public function show(): JsonResponse
    {
        try {
            $user = $this->authRepository->userLoggedIn();
            $userInformation = $this->userInformationRepository->show($user);
            if (!$userInformation) {
                return ApiResponse::success(
                    'No se encontró información del usuario',
                    404,
                );
            }
            return ApiResponse::success(
                'Información del usuario obtenida correctamente',
                200,
                new UserInformationResource($userInformation)
            );
        } catch (Exception $e) {
            return ApiResponse::error("Ha ocurrido un error: " . $e->getMessage(), 500);
        }
    }
}
