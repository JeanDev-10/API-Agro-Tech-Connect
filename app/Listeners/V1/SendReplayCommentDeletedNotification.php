<?php

namespace App\Listeners\V1;

use App\Events\V1\ReplayCommentDeletedByAdmin;
use App\Notifications\V1\ReplayCommentDeletedNotification;

class SendReplayCommentDeletedNotification
{
    public function handle(ReplayCommentDeletedByAdmin $event): void
    {
        $event->comment->user->notify(
            new ReplayCommentDeletedNotification(
                $event->comment->comment,
                $event->admin->name
            )
        );
    }
}
