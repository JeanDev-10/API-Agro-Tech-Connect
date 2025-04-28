<?php

namespace App\Repository\V1\User;

use App\Events\V1\UserChangePasswordEvent;
use App\Events\V1\UserDeletedAccountEvent;
use App\Interfaces\V1\User\UserRepositoryInterface;
use App\Models\V1\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserRepository implements UserRepositoryInterface
{
    public function changePassword($user, Request $request)
    {
        $user->password = bcrypt($request->new_password);
        $user->saveOldPassword();
        $user->save();
        event(new UserChangePasswordEvent($user));
    }
    public function deleteMe($user)
    {
        DB::beginTransaction();
        // Eliminar tokens
        $userData = $user->replicate();
        $userData->setHidden([]);
        $user->tokens()->delete();

        // Eliminar usuario
        $user->delete();
        DB::commit();
        event(new UserDeletedAccountEvent($userData));
    }
    public function mePosts($filters,$user_id)
    {
        $query = Post::where('user_id',$user_id)->with(['images','user.image'])
            ->withCount(['comments', 'reactions']);

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
