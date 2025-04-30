<?php

namespace App\Listeners\V1;

use App\Events\V1\PostDeletedByAdmin;
use App\Notifications\V1\PostDeletedNotification;

class SendPostDeletedNotification
{
    public function handle(PostDeletedByAdmin $event): void
    {
        $event->post->user->notify(
            new PostDeletedNotification(
                $event->post->title,
                $event->admin->name
            )
        );
    }
}
