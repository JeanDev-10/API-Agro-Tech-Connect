<?php

namespace App\Listeners\V1;

use App\Events\V1\UserChangePasswordEvent;
use App\Events\V1\UserCreateCommentComplaintEvent;
use App\Events\V1\UserCreatePostComplaintEvent;
use App\Events\V1\UserDeletedAccountEvent;
use App\Events\V1\UserRegisteredEvent;
use App\Models\V1\User;
use App\Notifications\V1\SendWelcomeNotification;
use App\Notifications\V1\UserChangePasswordNotification;
use App\Notifications\V1\UserCreateCommentComplaintNotification;
use App\Notifications\V1\UserCreatePostComplaintNotification;
use App\Notifications\V1\UserDeletedAccountNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class UserCreateCommentComplaintListener
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
    public function handle(UserCreateCommentComplaintEvent $event): void
    {
        $admins = User::role('admin')->get();
        Notification::send($admins, new UserCreateCommentComplaintNotification($event->complaint, $event->comment, $event->user));
    }
}
