<?php
namespace App\Repository\V1\Comment;
use App\Interfaces\V1\Comment\ReplayCommentRepositoryInterface;
use App\Models\V1\ReplayComment;

class ReplayCommentRepository implements ReplayCommentRepositoryInterface
{

	public function show($comment)
	{
        return ReplayComment::with(['images', 'user.image'])
        ->withCount(['reactions'])
        ->where('id', $comment)
        ->first();
	}
}
