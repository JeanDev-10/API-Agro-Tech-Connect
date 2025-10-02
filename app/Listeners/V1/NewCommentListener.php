<?php

namespace App\Listeners\V1;

use App\Events\V1\NewCommentEvent;
use App\Notifications\V1\NewCommentNotification;
use App\Notifications\V1\NewPostNotification;
use App\Traits\V1\SkipsSelfNotification;


class NewCommentListener
{
    use SkipsSelfNotification; //trait para evitar notificaciones a uno mismo

    public function handle(NewCommentEvent $event): void
    {
        if (!$this->shouldNotify($event->user, $event->post->user)) {
            return; // No enviar notificación si el usuario es el mismo
        }
        $event->post->user->notify(new NewCommentNotification($event->post, $event->comment,$event->user));
    }
}
