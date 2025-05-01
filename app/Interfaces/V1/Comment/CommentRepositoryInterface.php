<?php
namespace App\Interfaces\V1\Comment;

use App\Models\V1\Comment;

interface CommentRepositoryInterface{
    public function getReplayComments(Comment $comment);
    public function show($comment);

}
