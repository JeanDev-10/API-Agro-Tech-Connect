<?php

namespace App\Policies\V1;

use App\Models\V1\Comment;
use App\Models\V1\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Validation\UnauthorizedException;

class CommentPolicy
{


    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Comment $comment)
    {
        return $user->id === $comment->user_id
            ? Response::allow()
            : throw new UnauthorizedException("asdasds");
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user)
    {
        return $user->hasRole('admin')
            ? Response::allow()
            : throw new UnauthorizedException();

    }
}
