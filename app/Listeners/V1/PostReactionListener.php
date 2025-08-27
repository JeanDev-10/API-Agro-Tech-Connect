<?php

namespace App\Listeners\V1;

use App\Events\V1\CommentReactionEvent;
use App\Events\V1\PostReactionEvent;
use App\Models\V1\Range;
use App\Notifications\V1\NewReactionNotification;
use App\Notifications\V1\NewRangeAchievedNotification;
use App\Notifications\V1\NewReactionPostNotification;

class PostReactionListener
{
    /**
     * Handle the event.
     */
    public function handle(PostReactionEvent $event): void
    {
        // Notificar al dueño del comentario sobre la nueva reacción
        $event->post->user->notify(
            new NewReactionPostNotification($event->post, $event->reaction)
        );

        
    }
   
}
