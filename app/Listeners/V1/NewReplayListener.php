<?php

namespace App\Listeners\V1;

use App\Events\V1\NewCommentEvent;
use App\Events\V1\NewReplyEvent;
use App\Notifications\V1\NewCommentNotification;
use App\Notifications\V1\NewPostNotification;
use App\Notifications\V1\NewReplayCommentNotification;

class NewReplayListener
{
    public function handle(NewReplyEvent $event): void
    {
        $event->parentComment->user->notify(new NewReplayCommentNotification($event->reply, $event->parentComment,$event->replier));
    }
}
