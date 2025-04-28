<?php
namespace App\Interfaces\V1\User;

use Illuminate\Http\Request;

interface UserRepositoryInterface{
    public function changePassword($user,Request $request);
    public function deleteMe($user);
    public function mePosts($filters,$user_id);
    public function meFollowingPosts($filters,$user_id);
}
