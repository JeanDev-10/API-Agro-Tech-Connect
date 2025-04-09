<?php

namespace App\Repository\V1\User;

use App\Events\V1\UserFollowEvent;
use App\Interfaces\V1\User\FollowRepositoryInterface;
use App\Models\V1\Follow;
use App\Models\V1\User;
use App\Notifications\V1\NewFollowerNotification;
use Exception;

class FollowRepository implements FollowRepositoryInterface
{
    public function followUser(User $follower, User $followed): Follow
    {
        // Verificar si ya existe el seguimiento
        if ($this->isFollowing($follower, $followed)) {
            throw new Exception('Ya estás siguiendo a este usuario.');
        }

        // Verificar que no sea el mismo usuario
        if ($follower->id === $followed->id) {
            throw new Exception('No puedes seguirte a ti mismo.');
        }

        // Verificar que no sea el admin (id=1)
        if ($followed->id === 1) {
            throw new Exception('No puedes seguir al administrador.');
        }

        $follow = Follow::create([
            'follower_id' => $follower->id,
            'followed_id' => $followed->id
        ]);

        // Enviar notificación
        event(new UserFollowEvent($follower, $followed));

        return $follow;
    }

    public function unfollowUser(User $follower, User $followed): bool
    {
        $follow = Follow::where('follower_id', $follower->id)
            ->where('followed_id', $followed->id)
            ->first();

        if (!$follow) {
            throw new Exception('No estás siguiendo a este usuario.');
        }

        return $follow->delete();
    }

    public function isFollowing(User $follower, User $followed): bool
    {
        return Follow::where('follower_id', $follower->id)
            ->where('followed_id', $followed->id)
            ->exists();
    }

    public function getUserFollowers(User $user)
    {
        return $user->followerUsers()->paginate(10);
    }

    public function getUserFollowing(User $user)
    {
        return $user->followingUsers()->paginate(10);
    }
}
