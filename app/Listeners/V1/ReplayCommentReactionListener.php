<?php

namespace App\Listeners\V1;

use App\Events\V1\ReplayCommentReactionEvent;
use App\Notifications\V1\NewReactionReplayCommentNotification;
use App\Traits\V1\SkipsSelfNotification;

class ReplayCommentReactionListener
{
    use SkipsSelfNotification; //trait para evitar notificaciones a uno mismo
    /**
     * Handle the event.
     */
    public function handle(ReplayCommentReactionEvent $event): void
    {
        if (!$this->shouldNotify($event->reaction->user, $event->replayComment->user)) {
            return; // No enviar notificación si el usuario es el mismo
        }
        // Notificar al dueño del comentario sobre la nueva reacción
        $event->replayComment->user->notify(
            new NewReactionReplayCommentNotification($event->replayComment, $event->reaction)
        );


    }

}
