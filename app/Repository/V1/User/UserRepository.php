<?php

namespace App\Repository\V1\User;

use App\Events\V1\UserChangePasswordEvent;
use App\Events\V1\UserDeletedAccountEvent;
use App\Interfaces\V1\User\UserRepositoryInterface;
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
        event(new UserDeletedAccountEvent($userData));
        $user->tokens()->delete();

        // Eliminar usuario
        $user->delete();
        DB::commit();
    }
}
