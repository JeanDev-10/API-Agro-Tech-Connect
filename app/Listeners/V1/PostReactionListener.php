<?php

namespace App\Listeners\V1;

use App\Events\V1\CommentReactionEvent;
use App\Events\V1\PostReactionEvent;
use App\Models\V1\Range;
use App\Notifications\V1\NewReactionNotification;
use App\Notifications\V1\NewRangeAchievedNotification;
use App\Notifications\V1\NewReactionPostNotification;
use App\Traits\V1\SkipsSelfNotification;

class PostReactionListener
{
    use SkipsSelfNotification; //trait para evitar notificaciones a uno mismo

    /**
     * Handle the event.
     */
    public function handle(PostReactionEvent $event): void
    {
        if (!$this->shouldNotify($event->reaction->user, $event->post->user)) {
            return; // No enviar notificación si el usuario es el mismo
        }
        // Notificar al dueño del comentario sobre la nueva reacción
        $event->post->user->notify(
            new NewReactionPostNotification($event->post, $event->reaction)
        );


    }

}
