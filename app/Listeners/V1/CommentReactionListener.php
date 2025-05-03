<?php

namespace App\Listeners\V1;

use App\Events\V1\CommentReactionEvent;
use App\Models\V1\Range;
use App\Notifications\V1\NewReactionNotification;
use App\Notifications\V1\NewRangeAchievedNotification;

class CommentReactionListener
{
    /**
     * Handle the event.
     */
    public function handle(CommentReactionEvent $event): void
    {
        // Notificar al dueño del comentario sobre la nueva reacción
        $event->comment->user->notify(
            new NewReactionNotification($event->comment, $event->reaction)
        );

        // Verificar y actualizar rango del usuario
        $this->checkAndUpdateUserRange($event->comment->user);
    }

    /**
     * Verificar y actualizar el rango del usuario
     */
    protected function checkAndUpdateUserRange($user): void
    {
        $positiveReactionsCount = $user->comments()
            ->withCount('positiveReactions')
            ->get()
            ->sum('positive_reactions_count');

        $newRange = Range::where('min_range', '<=', $positiveReactionsCount)
            ->where(function ($query) use ($positiveReactionsCount) {
                $query->where('max_range', '>=', $positiveReactionsCount)
                    ->orWhereNull('max_range');
            })
            ->orderByDesc('min_range')
            ->first();

        // Verificar si el usuario ya tiene este rango
        if ($newRange && !$user->hasRange($newRange)) {
            // Asignar el nuevo rango
            $user->ranges()->attach($newRange, ['achieved_at' => now()]);

            // Notificar al usuario sobre el nuevo rango
            $user->notify(
                new NewRangeAchievedNotification($newRange)
            );
        }
    }
}
