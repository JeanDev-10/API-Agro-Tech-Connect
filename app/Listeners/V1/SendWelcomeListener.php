<?php

namespace App\Listeners\V1;

use App\Events\V1\UserRegisteredEvent;
use App\Notifications\V1\SendWelcomeNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendWelcomeListener
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
    public function handle(UserRegisteredEvent $event): void
    {
        Notification::send($event->user, new SendWelcomeNotification());
    }
}
