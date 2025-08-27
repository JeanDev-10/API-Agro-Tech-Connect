<?php

namespace App\Listeners\V1;

use App\Events\V1\NewCommentEvent;
use App\Notifications\V1\NewCommentNotification;
use App\Notifications\V1\NewPostNotification;

class NewCommentListener
{
    public function handle(NewCommentEvent $event): void
    {
        $event->post->user->notify(new NewCommentNotification($event->post, $event->comment,$event->user));
    }
}
