<?php

namespace App\Repository\V1\User;

use App\Http\Responses\V1\ApiResponse;
use App\Interfaces\V1\User\UserInformationRepositoryInterface;
use App\Models\V1\UserInformation;
use Illuminate\Http\Request;

class UserInformationRepository implements UserInformationRepositoryInterface
{
	public function show($user)
	{
		$userInformation = UserInformation::where('user_id', $user->id)->first();
        return $userInformation;
	}

	public function storeOrUpdate($user, Request $request)
	{
		// Buscar o crear información del usuario
        $userInformation = UserInformation::updateOrCreate(
            ['user_id' => $user->id],
            $request->validated()
        );
        return $userInformation;
	}
}
