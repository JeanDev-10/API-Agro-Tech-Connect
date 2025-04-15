<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\FollowRequest;
use App\Http\Resources\V1\User\FollowResource;
use App\Http\Responses\V1\ApiResponse;
use App\Models\V1\User;
use App\Repository\V1\Auth\AuthRepository;
use App\Repository\V1\User\FollowRepository;
use Exception;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class FollowController extends Controller
{
    public function __construct(
        private FollowRepository $followRepo,
        private AuthRepository $authRepository
    ) {}

    public function follow(FollowRequest $request)
    {
        try {
            DB::beginTransaction();

            $follower = $this->authRepository->userLoggedIn();
            $followed = $request->getDecryptedUserId();
            $userFollowed = User::find($followed);
            $userFollower = User::find($follower->id);
            if (!$userFollowed) {
                return ApiResponse::error(
                    'El usuario no existe',
                    404
                );
            }
            $follow = $this->followRepo->followUser($userFollower, $userFollowed);

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
            $userFollowed = User::find($followed);
            if (!$userFollowed) {
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

    public function followers($id)
    {
        try {
            $id = Crypt::decrypt($id);
            $user = User::where('id', $id)->first();
            if (!$user) {
                return ApiResponse::error(
                    'El usuario no existe',
                    404
                );
            }
            $followers = $this->followRepo->getUserFollowers($user);

            return ApiResponse::success(
                'Lista de seguidores obtenida',
                200,
                $followers->through(function ($item) {
                    return new FollowResource($item);
                })
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                'Error al obtener seguidores: ' . $e->getMessage(),
                500
            );
        }
    }

    public function following($id)
    {
        try {
            $id = Crypt::decrypt($id);
            $user = User::where('id', $id)->first();
            if (!$user) {
                return ApiResponse::error(
                    'El usuario no existe',
                    404
                );
            }
            $following = $this->followRepo->getUserFollowing($user);

            return ApiResponse::success(
                'Lista de seguidos obtenida',
                200,
                $following->through(function ($item) {
                    return new FollowResource($item);
                })
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                'Error al obtener usuarios seguidos: ' . $e->getMessage(),
                500
            );
        }
    }
    public function meFollowers()
    {
        try {
            $user = $this->authRepository->userLoggedIn();
            $followers = $this->followRepo->getUserFollowers($user);
            return ApiResponse::success(
                'Lista de seguidos obtenida',
                200,
                $followers->through(function ($item) {
                    return new FollowResource($item);
                })
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                'Error al obtener seguidores: ' . $e->getMessage(),
                500
            );
        }
    }

    public function meFollowing()
    {
        try {
            $user = $this->authRepository->userLoggedIn();
            $following = $this->followRepo->getUserFollowing($user);
            return ApiResponse::success(
                'Lista de seguidos obtenida',
                200,
                $following->through(function ($item) {
                    return new FollowResource($item);
                })
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
        return match ($e->getMessage()) {
            'Ya estás siguiendo a este usuario.' => 409,
            'No estás siguiendo a este usuario.' => 404,
            'No puedes seguirte a ti mismo.',
            'No puedes seguir al administrador.' => 403,
            default => 500
        };
    }
}
