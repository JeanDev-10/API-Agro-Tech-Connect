<?php

namespace App\Traits\V1;

trait SkipsSelfNotification
{
    protected function shouldNotify($actor, $receiver)
    {
        return $actor->id !== $receiver->id;
    }
}
