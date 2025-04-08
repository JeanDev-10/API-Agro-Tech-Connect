<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\AvatarRequest;
use App\Http\Responses\V1\ApiResponse;
use App\Repository\V1\Auth\AuthRepository;
use App\Repository\V1\User\AvatarRepository;
use Exception;
use Illuminate\Support\Facades\DB;

class AvatarController extends Controller
{
    public function __construct(
        private AvatarRepository $avatarRepository,
        private AuthRepository $authRepository
    ) {}

    /**
     * Actualiza el avatar del usuario
     *
     * @param AvatarRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(AvatarRequest $request)
    {
        try {
            Db::beginTransaction();
            $user = $this->authRepository->userLoggedIn();
            $avatar = $request->file('avatar');
            $image = $this->avatarRepository->updateOrCreateAvatar($user, $avatar);
            Db::commit();
            return ApiResponse::success(
                'Avatar actualizado correctamente',
                200,
                ['avatar_url' => $image->url]
            );
        } catch (Exception $e) {
            Db::rollBack();
            return ApiResponse::error(
                'Error al actualizar el avatar: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Elimina el avatar del usuario
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy()
    {
        try {
            Db::beginTransaction();
            $user = $this->authRepository->userLoggedIn();
            if (!$user->image) {
                return ApiResponse::error(
                    'El usuario no tiene un avatar para eliminar',
                    404
                );
            }
            $this->avatarRepository->deleteAvatar($user);
            Db::commit();
            return ApiResponse::success(
                'Avatar eliminado correctamente',
                200
            );
        } catch (Exception $e) {
            DB::rollBack();
            return ApiResponse::error(
                'Error al eliminar el avatar: ' . $e->getMessage(),
                500
            );
        }
    }
}
