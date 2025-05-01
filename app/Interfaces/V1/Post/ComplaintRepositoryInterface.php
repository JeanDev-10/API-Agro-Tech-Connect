<?php
namespace App\Interfaces\V1\Post;

use App\Models\V1\Post;
use App\Models\V1\User;

interface ComplaintRepositoryInterface{
    public function createComplaint(array $data);
    public function hasReachedComplaintLimit(User $user, Post $post);
    public function getPostComplaints(Post $post);
    public function hasReachedComplaintLimitComment($user,$comment);
    public function hasReachedComplaintLimitReplayComment($user,$comment);
}
