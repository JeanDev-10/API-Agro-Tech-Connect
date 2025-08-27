<?php

namespace App\Listeners\V1;

use App\Events\V1\UserCreateReplayCommentComplaintEvent;
use App\Models\V1\User;
use App\Notifications\V1\UserCreateReplayCommentComplaintNotification;
use Illuminate\Support\Facades\Notification;

class UserCreateReplayCommentComplaintListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserCreateReplayCommentComplaintEvent $event): void
    {
        $admins = User::role('admin')->get();
        Notification::send($admins, new UserCreateReplayCommentComplaintNotification($event->complaint, $event->comment, $event->user));
    }
}
