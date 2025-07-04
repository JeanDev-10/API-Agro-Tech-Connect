<?php

namespace App\Events\V1;

use App\Models\V1\Comment;
use App\Models\V1\Reaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentReactionEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $comment;
    public $reaction;
    public $isNewReaction;

    /**
     * Create a new event instance.
     */
    public function __construct(Comment $comment, Reaction $reaction)
    {
        $this->comment = $comment;
        $this->reaction = $reaction;
    }
}
