<?php
namespace App\Repository\V1\Comment;
use App\Interfaces\V1\Comment\CommentRepositoryInterface;
use App\Models\V1\Comment;

class CommentRepository implements CommentRepositoryInterface
{
	public function getReplayComments(Comment $comment)
	{
        return $comment->replies()
        ->with(['images', 'user.image'])
        ->withCount(['reactions'])
        ->latest()
        ->paginate(10);
	}
}
