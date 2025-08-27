<?php

namespace App\Repository\V1\Post;

use App\Interfaces\V1\Post\ComplaintRepositoryInterface;
use App\Models\V1\Comment;
use App\Models\V1\Complaint;
use App\Models\V1\Post;
use App\Models\V1\ReplayComment;
use App\Models\V1\User;

class ComplaintRepository implements ComplaintRepositoryInterface
{
    const MAX_COMPLAINTS_PER_USER = 5;

    public function createComplaint(array $data)
    {
        return Complaint::create($data);
    }

    public function hasReachedComplaintLimit($user,$post): bool
    {
        $complaintsCount = Complaint::where('user_id', $user->id)
            ->where('complaintable_id', $post->id)
            ->where('complaintable_type', Post::class)
            ->count();

        return $complaintsCount >= self::MAX_COMPLAINTS_PER_USER;
    }
    public function hasReachedComplaintLimitComment($user,$comment): bool
    {
        $complaintsCount = Complaint::where('user_id', $user->id)
            ->where('complaintable_id', $comment->id)
            ->where('complaintable_type', Comment::class)
            ->count();

        return $complaintsCount >= self::MAX_COMPLAINTS_PER_USER;
    }
    public function hasReachedComplaintLimitReplayComment($user,$comment): bool
    {
        $complaintsCount = Complaint::where('user_id', $user->id)
            ->where('complaintable_id', $comment->id)
            ->where('complaintable_type', ReplayComment::class)
            ->count();

        return $complaintsCount >= self::MAX_COMPLAINTS_PER_USER;
    }

    public function getPostComplaints(Post $post)
    {
        return $post->complaints()->with('user')->paginate(10);
    }
}
