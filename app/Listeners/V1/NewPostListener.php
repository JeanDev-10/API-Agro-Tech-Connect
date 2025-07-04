<?php

namespace App\Listeners\V1;

use App\Events\V1\NewPostEvent;
use App\Notifications\V1\NewPostNotification;

class NewPostListener
{
    public function handle(NewPostEvent $event): void
    {
        $post = $event->post;
        $followers = $post->user->followers()->with('follower')->get();
        foreach ($followers as $follower) {
            $follower->follower->notify(new NewPostNotification($post));
        }
    }
}
