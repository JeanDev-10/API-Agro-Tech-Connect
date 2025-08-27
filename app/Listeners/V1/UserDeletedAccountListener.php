<?php

namespace App\Listeners\V1;

use App\Events\V1\UserChangePasswordEvent;
use App\Events\V1\UserDeletedAccountEvent;
use App\Events\V1\UserRegisteredEvent;
use App\Notifications\V1\SendWelcomeNotification;
use App\Notifications\V1\UserChangePasswordNotification;
use App\Notifications\V1\UserDeletedAccountNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class UserDeletedAccountListener
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
    public function handle(UserDeletedAccountEvent $event): void
    {
        Notification::send($event->user,new UserDeletedAccountNotification());
    }
}
