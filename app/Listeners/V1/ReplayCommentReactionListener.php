<?php

namespace App\Listeners\V1;

use App\Events\V1\ReplayCommentReactionEvent;
use App\Notifications\V1\NewReactionReplayCommentNotification;

class ReplayCommentReactionListener
{
    /**
     * Handle the event.
     */
    public function handle(ReplayCommentReactionEvent $event): void
    {
        // Notificar al dueño del comentario sobre la nueva reacción
        $event->replayComment->user->notify(
            new NewReactionReplayCommentNotification($event->replayComment, $event->reaction)
        );


    }

}
