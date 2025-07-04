<?php

namespace App\Events\V1;

use App\Models\V1\Post;
use App\Models\V1\Reaction;
use App\Models\V1\ReplayComment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReplayCommentReactionEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    /**
     * Create a new event instance.
     */
    public function __construct(public ReplayComment $replayComment,public Reaction $reaction)
    {
    }
}
