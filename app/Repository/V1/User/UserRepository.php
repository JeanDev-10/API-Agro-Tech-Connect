<?php

namespace App\Repository\V1\User;

use App\Events\V1\UserChangePasswordEvent;
use App\Events\V1\UserDeletedAccountEvent;
use App\Interfaces\V1\User\UserRepositoryInterface;
use App\Models\V1\Post;
use App\Services\V1\ImageService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserRepository implements UserRepositoryInterface
{

    public function __construct(private readonly ImageService $imageService) {}
    public function changePassword($user, Request $request)
    {
        $user->password = bcrypt($request->new_password);
        $user->saveOldPassword();
        $user->save();
        event(new UserChangePasswordEvent($user));
    }
    public function deleteMe($user,$social=false)
    {
        DB::beginTransaction();
        // Eliminar tokens
        $userData = $user->replicate();
        $userData->setHidden([]);
        // Eliminar imagen de perfil si existe y si es local
        if(!$social){
            if ($user->image()->exists()) {
                $fileDeleted = $this->imageService->deleteImage($user->image->image_Uuid);
                if (!$fileDeleted) {
                    throw new Exception("No se pudo eliminar el archivo físico del avatar");
                }
                $user->image()->delete();
            }
        }
        $user->tokens()->delete();
        // Eliminar usuario
        $user->delete();
        DB::commit();
        event(new UserDeletedAccountEvent($userData));
    }
    public function deleteUserAdmin($user)
    {
        // Eliminar tokens
        $userData = $user->replicate();
        $userData->setHidden([]);
        if ($user->image()->exists()) {
            $fileDeleted = $this->imageService->deleteImage($user->image->image_Uuid);
            if (!$fileDeleted) {
                throw new Exception("No se pudo eliminar el archivo físico del avatar");
            }
            $user->image()->delete();
        }
        $user->tokens()->delete();
        // Eliminar usuario
        $user->delete();
        event(new UserDeletedAccountEvent($userData));
    }
    public function mePosts($filters, $user_id)
    {
        $query = Post::where('user_id', $user_id)->with(['images', 'user.image', 'user.ranges'])
            ->withCount(['comments', 'reactions'])->latest();;

        // Filtro por año y mes
        if (isset($filters['year'])) {
            $query->whereYear('created_at', $filters['year']);
        }

        if (isset($filters['month'])) {
            $query->whereMonth('created_at', $filters['month']);
        }

        // Búsqueda avanzada por texto o palabras clave
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->paginate(10);
    }
    public function meFollowingPosts($filters, $user_id)
    {

        $query =  Post::whereHas('user', function ($query) use ($user_id) {
            // Solo posts de usuarios que el usuario actual sigue
            $query->whereHas('followers', function ($q) use ($user_id) {
                $q->where('follower_id', $user_id);
            });
        })->with(['images', 'user.image', 'user.ranges'])
            ->withCount(['comments', 'reactions'])->latest();;

        // Filtro por año y mes
        if (isset($filters['year'])) {
            $query->whereYear('created_at', $filters['year']);
        }

        if (isset($filters['month'])) {
            $query->whereMonth('created_at', $filters['month']);
        }

        // Búsqueda avanzada por texto o palabras clave
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->paginate(10);
    }
    public function userPosts($filters, $user_id)
    {
        $query =  Post::where('user_id', $user_id)->with(['images', 'user.image', 'user.ranges'])
            ->withCount(['comments', 'reactions'])->latest();;

        // Filtro por año y mes
        if (isset($filters['year'])) {
            $query->whereYear('created_at', $filters['year']);
        }

        if (isset($filters['month'])) {
            $query->whereMonth('created_at', $filters['month']);
        }

        // Búsqueda avanzada por texto o palabras clave
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->paginate(10);
    }
}
