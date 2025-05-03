<?php

namespace App\Events\V1;

use App\Models\V1\Post;
use App\Models\V1\Reaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostReactionEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $post;
    public $reaction;

    /**
     * Create a new event instance.
     */
    public function __construct(Post $post, Reaction $reaction)
    {
        $this->post = $post;
        $this->reaction = $reaction;
    }
}
