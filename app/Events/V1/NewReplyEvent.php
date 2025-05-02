<?php

namespace App\Events\V1;

use App\Models\V1\Comment;
use App\Models\V1\ReplayComment;
use App\Models\V1\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewReplyEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $parentComment;
    public $reply;
    public $replier;

    public function __construct(Comment $parentComment, ReplayComment $reply, User $replier)
    {
        $this->parentComment = $parentComment;
        $this->reply = $reply;
        $this->replier = $replier;
    }
}