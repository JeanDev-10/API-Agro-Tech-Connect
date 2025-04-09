<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\FollowRequest;
use App\Http\Responses\V1\ApiResponse;
use App\Models\V1\User;
use App\Repository\V1\Auth\AuthRepository;
use App\Repository\V1\User\FollowRepository;
use Exception;
use Illuminate\Support\Facades\DB;

class FollowController extends Controller
{
    public function __construct(private FollowRepository $followRepo,
    private AuthRepository $authRepository
    )
    {
    }

    public function follow(FollowRequest $request)
    {
        try {
            DB::beginTransaction();

            $follower = $this->authRepository->userLoggedIn();
            $followed = $request->getDecryptedUserId();
            $userFollowed=User::find($followed);
            if(!$userFollowed){
                return ApiResponse::error(
                    'El usuario no existe',
                    404
                );
            }

            $follow = $this->followRepo->followUser($follower, $userFollowed);

            DB::commit();

            return ApiResponse::success(
                'Ahora estás siguiendo a este usuario',
                201,
                ['follow_id' => $follow->id]
            );

        } catch (Exception $e) {
            DB::rollBack();
            return ApiResponse::error(
                $e->getMessage(),
                $this->getStatusCodeFromException($e)
            );
        }
    }

    public function unfollow(FollowRequest $request)
    {
        try {
            DB::beginTransaction();

            $follower = $this->authRepository->userLoggedIn();
            $followed = $request->getDecryptedUserId();
            $userFollowed=User::find($followed);
            if(!$userFollowed){
                return ApiResponse::error(
                    'El usuario no existe',
                    404
                );
            }
            $this->followRepo->unfollowUser($follower, $userFollowed);

            DB::commit();

            return ApiResponse::success(
                'Has dejado de seguir a este usuario',
                200
            );

        } catch (Exception $e) {
            DB::rollBack();
            return ApiResponse::error(
                $e->getMessage(),
                $this->getStatusCodeFromException($e)
            );
        }
    }

    public function followers(User $user)
    {
        try {
            $followers = $this->followRepo->getUserFollowers($user);

            return ApiResponse::success(
                'Lista de seguidores obtenida',
                200,
                ['followers' => $followers]
            );

        } catch (Exception $e) {
            return ApiResponse::error(
                'Error al obtener seguidores: ' . $e->getMessage(),
                500
            );
        }
    }

    public function following(User $user)
    {
        try {
            $following = $this->followRepo->getUserFollowing($user);

            return ApiResponse::success(
                'Lista de seguidos obtenida',
                200,
                ['following' => $following]
            );

        } catch (Exception $e) {
            return ApiResponse::error(
                'Error al obtener usuarios seguidos: ' . $e->getMessage(),
                500
            );
        }
    }

    protected function getStatusCodeFromException(Exception $e): int
    {
        return match($e->getMessage()) {
            'Ya estás siguiendo a este usuario.' => 409,
            'No estás siguiendo a este usuario.' => 404,
            'No puedes seguirte a ti mismo.',
            'No puedes seguir al administrador.' => 403,
            default => 500
        };
    }
}
