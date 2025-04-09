<?php

namespace App\Listeners\V1;

use App\Events\V1\UserFollowEvent;
use App\Notifications\V1\UserFollowNotification;

class UserFollowListener
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
    public function handle(UserFollowEvent $event): void
    {
        $event->followed->notify(
            new UserFollowNotification($event->follower)
        );
    }
}
