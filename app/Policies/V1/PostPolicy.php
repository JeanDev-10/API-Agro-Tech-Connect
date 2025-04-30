<?php

namespace App\Policies\V1;

use App\Models\V1\Post;
use App\Models\V1\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Validation\UnauthorizedException;

class PostPolicy
{


    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Post $post)
    {
        return $user->id === $post->user_id
            ? Response::allow()
            : throw new UnauthorizedException();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Post $post)
    {
        return $user->id === $post->user_id || $user->hasRole('admin')
            ? Response::allow()
            : throw new UnauthorizedException();

    }
}
