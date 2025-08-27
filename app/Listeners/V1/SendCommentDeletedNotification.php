<?php

namespace App\Listeners\V1;

use App\Events\V1\CommentDeletedByAdmin;
use App\Events\V1\ReplayCommentDeletedByAdmin;
use App\Notifications\V1\CommentDeletedNotification;

class SendCommentDeletedNotification
{
    public function handle(CommentDeletedByAdmin $event): void
    {
        $event->comment->user->notify(
            new CommentDeletedNotification(
                $event->comment->comment,
                $event->admin->name
            )
        );
    }
}
