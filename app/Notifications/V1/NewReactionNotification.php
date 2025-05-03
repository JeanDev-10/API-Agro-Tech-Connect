<?php

namespace App\Notifications\V1;

use App\Models\V1\Comment;
use App\Models\V1\Reaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;

class NewReactionNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public $tries = 3; // Número de reintentos

    public $comment;
    public $reaction;

    /**
     * Create a new notification instance.
     */
    public function __construct(Comment $comment, Reaction $reaction)
    {
        $this->comment = $comment;
        $this->reaction = $reaction;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $reactionType = $this->reaction->type === 'positivo' ? 'positivo' : 'negativo';
        $url = config('app.frontend_url') . '/comment/' . Crypt::encrypt($this->comment->id);

        return (new MailMessage)
            ->subject("Tu comentario recibió un $reactionType")
            ->line("Tu comentario en la publicación '{$this->comment->post->title}' ha recibido un $reactionType.")
            ->action('Ver comentario', $url)
            ->line('¡Gracias por participar en nuestra comunidad!');
    }

    /**
     * Get the array representation for database storage.
     */
    public function toArray(object $notifiable): array
    {
        $url = config('app.frontend_url') . '/comment/' . Crypt::encrypt($this->comment->id);

        return [
            'type' => 'new_reaction',
            'comment_id' => $this->comment->id,
            'reaction_id' => $this->reaction->id,
            'reaction_type' => $this->reaction->type,
            'post_title' => $this->comment->post->title,
            'comment_content' => $this->comment->comment,
            'link' => $url,
            'message' => "Tu comentario recibió un {$this->reaction->type}"
        ];
    }
}
