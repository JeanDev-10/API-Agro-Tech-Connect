<?php
namespace App\Interfaces\V1\User;

use App\Models\V1\User;
use Illuminate\Http\Request;

interface FollowRepositoryInterface{
    public function followUser(User $follower, User $followed);
    public function unfollowUser(User $follower, User $followed);
    public function isFollowing(User $follower, User $followed);
    public function getUserFollowers(User $user);
    public function getUserFollowing(User $user);
}
