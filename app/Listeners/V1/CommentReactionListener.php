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

        if ($event->reaction->type === 'positivo') {
            // Verificar y actualizar rango del usuario
            $this->checkAndUpdateUserRange($event->comment->user);
        }
    }

    /**
     * Verificar y actualizar el rango del usuario
     */
    protected function checkAndUpdateUserRange($user): void
    {
        $positiveReactionsCount = $user->comments()
            ->withCount(['positiveReactions' => function ($query) {
                $query->where('type', 'positivo');
            }])
            ->get()
            ->sum('positive_reactions_count');
        $ranges = Range::orderBy('min_range')->get();
        foreach ($ranges as $range) {
            if (
                $positiveReactionsCount >= $range->min_range &&
                ($range->max_range === null || $positiveReactionsCount <= $range->max_range)
            ) {

                // Asignar rango si no lo tiene ya
                if (!$user->ranges()->where('range_id', $range->id)->exists()) {
                    $user->ranges()->attach($range, ['achieved_at' => now()]);
                    $user->notify(
                        new NewRangeAchievedNotification($range)
                    );
                }
            }
        }
    }
}
