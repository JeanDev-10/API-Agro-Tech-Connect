<?php

namespace App\Listeners\V1;

use App\Events\V1\NewCommentEvent;
use App\Events\V1\NewReplyEvent;
use App\Notifications\V1\NewCommentNotification;
use App\Notifications\V1\NewPostNotification;
use App\Notifications\V1\NewReplayCommentNotification;
use App\Traits\V1\SkipsSelfNotification;

class NewReplayListener
{
    use SkipsSelfNotification; //trait para evitar notificaciones a uno mismo

    public function handle(NewReplyEvent $event): void
    {
        if (!$this->shouldNotify($event->replier, $event->parentComment->user)) {
            return; // No enviar notificación si el usuario es el mismo
        }
         $
        $event->parentComment->user->notify(new NewReplayCommentNotification($event->reply, $event->parentComment,$event->replier));
    }
}
