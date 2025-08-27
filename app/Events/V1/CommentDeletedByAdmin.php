<?php

namespace App\Events\V1;

use App\Models\V1\Post;
use App\Models\V1\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentDeletedByAdmin
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public  $comment,
        public  $admin
    ) {}
}
