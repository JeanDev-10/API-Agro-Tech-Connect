<?php
namespace App\Interfaces\V1\User;

use Illuminate\Http\Request;

interface UserRepositoryInterface{
    public function changePassword($user,Request $request);
}
