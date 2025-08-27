<?php
namespace App\Interfaces\V1\User;

use Illuminate\Http\Request;

interface UserInformationRepositoryInterface{
    public function storeOrUpdate($user,Request $request);
    public function show($user);
}
