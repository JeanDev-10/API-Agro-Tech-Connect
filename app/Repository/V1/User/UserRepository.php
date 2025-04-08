<?php

namespace App\Repository\V1\User;

use App\Events\V1\UserChangePasswordEvent;
use App\Interfaces\V1\User\UserRepositoryInterface;
use Illuminate\Http\Request;

class UserRepository implements UserRepositoryInterface
{
	public function changePassword($user,Request $request)
	{
		$user->password = bcrypt($request->new_password);
        $user->saveOldPassword();
		$user->save();
        event(new UserChangePasswordEvent($user));


	}
}
